<?php

namespace Inovector\Mixpost\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Inovector\Mixpost\Actions\UpdateOrCreateAccount;
use Inovector\Mixpost\Concerns\UsesSocialProviderManager;
use Inovector\Mixpost\Enums\ServiceGroup;
use Inovector\Mixpost\Facades\ServiceManager;
use Inovector\Mixpost\Http\Resources\AccountResource;
use Inovector\Mixpost\Models\Account;
use Inovector\Mixpost\Models\Entity;
use Inovector\Mixpost\Http\Resources\EntityResource;

class AccountsController extends Controller
{
    use UsesSocialProviderManager;

    public function index(): Response
    {
        $socialServices = ServiceManager::services()->group(ServiceGroup::SOCIAL)->getNames();

        return Inertia::render('Accounts/Accounts', [
            'accounts' => AccountResource::collection(Account::with('entity')->latest()->get())->resolve(),
            'entities' => EntityResource::collection(Entity::orderBy('sort_order')->get())->resolve(),
            'is_configured_service' => ServiceManager::isConfigured($socialServices),
            'is_service_active' => ServiceManager::isActive($socialServices),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $account = Account::firstOrFailByUuid($request->route('account'));

        // Handle entity assignment
        if ($request->has('entity_id')) {
            $request->validate([
                'entity_id' => ['nullable', 'exists:mixpost_entities,id'],
            ]);
            
            $account->update(['entity_id' => $request->input('entity_id')]);
            return redirect()->back()->with('success', 'Account entity updated.');
        }

        // Handle account refresh from provider
        $connection = $this->connectProvider($account);

        $response = $connection->getAccount();

        if ($response->hasError()) {
            if ($response->isUnauthorized()) {
                return redirect()->back()->with('error', 'The account cannot be updated. Re-authenticate your account.');
            }

            return redirect()->back()->with('error', 'The account cannot be updated.');
        }

        (new UpdateOrCreateAccount())($account->provider, $response->context(), $account->access_token->toArray());

        return redirect()->back();
    }

    public function delete(Request $request): RedirectResponse
    {
        $account = Account::firstOrFailByUuid($request->route('account'));

        $connection = $this->connectProvider($account);

        if (method_exists($connection, 'revokeToken')) {
            $connection->revokeToken();
        }

        $account->delete();

        return redirect()->back();
    }
}
