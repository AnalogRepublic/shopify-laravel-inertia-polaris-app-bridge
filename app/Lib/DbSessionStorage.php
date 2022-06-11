<?php

declare(strict_types=1);

namespace App\Lib;

use Exception;
use Shopify\Auth\AccessTokenOnlineUserInfo;
use Shopify\Auth\Session;
use Shopify\Auth\SessionStorage;
use Illuminate\Support\Facades\Log;
use App\Models\Session as SessionModel;
use App\Models\User;

class DbSessionStorage implements SessionStorage
{
    public function loadSession(string $sessionId): ?Session
    {
        $dbSession = SessionModel::where('session_id', $sessionId)->first();

        if ($dbSession) {
            $session = new Session(
                $dbSession->session_id,
                $dbSession->shop,
                (bool) $dbSession->is_online,
                $dbSession->state
            );

            if ($dbSession->expires_at) {
                $session->setExpires($dbSession->expires_at);
            }

            if ($dbSession->access_token) {
                $session->setAccessToken($dbSession->access_token);
            }

            if ($dbSession->scope) {
                $session->setScope($dbSession->scope);
            }

            if ($dbSession->user) {
                $user = $dbSession->user;
                $onlineAccessInfo = new AccessTokenOnlineUserInfo(
                    (int) $user->shopify_user_id,
                    $user->first_name,
                    $user->last_name,
                    $user->email,
                    (bool) $user->email_verified,
                    (bool) $user->account_owner,
                    $user->locale,
                    (bool) $user->collaborator
                );
                $session->setOnlineAccessInfo($onlineAccessInfo);
            }

            return $session;
        }

        return null;
    }

    public function storeSession(Session $session): bool
    {
        $dbSession = SessionModel::where('session_id', $session->getId())->first();

        if (!$dbSession) {
            $dbSession = new SessionModel();
        }
        $dbSession->session_id = $session->getId();
        $dbSession->shop = $session->getShop();
        $dbSession->state = $session->getState();
        $dbSession->is_online = $session->isOnline();
        $dbSession->access_token = $session->getAccessToken();
        $dbSession->expires_at = $session->getExpires();
        $dbSession->scope = $session->getScope();

        if (!empty($session->getOnlineAccessInfo())) {
            $user = User::firstOrCreate([
                'shop' => $dbSession->shop,
                'email' => $session->getOnlineAccessInfo()->getEmail(),
                'shopify_user_id' => $session->getOnlineAccessInfo()->getId()
            ]);
            $user->first_name = $session->getOnlineAccessInfo()->getFirstName();
            $user->last_name = $session->getOnlineAccessInfo()->getLastName();
            Log::info($user->email_verified_at || ($session->getOnlineAccessInfo()->isEmailVerified() ? now() : null));
            $user->email_verified_at = $user->email_verified_at ?? ($session->getOnlineAccessInfo()->isEmailVerified() ? now() : null);
            $user->account_owner = $session->getOnlineAccessInfo()->isAccountOwner();
            $user->locale = $session->getOnlineAccessInfo()->getLocale();
            $user->collaborator = $session->getOnlineAccessInfo()->isCollaborator();
        }

        try {
            return $dbSession->save();
        } catch (Exception $err) {
            Log::error("Failed to save session to database: " . $err->getMessage());
            return false;
        }
    }

    public function deleteSession(string $sessionId): bool
    {
        return SessionModel::where('session_id', $sessionId)->delete() === 1;
    }
}
