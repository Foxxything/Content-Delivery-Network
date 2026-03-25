<?php

namespace Foxxything\CDN\Core;

use GuzzleHttp\Exception\GuzzleException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use RuntimeException;
use Wohali\OAuth2\Client\Provider\Discord;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Wohali\OAuth2\Client\Provider\DiscordResourceOwner;

/**
 * DiscordAuth
 *
 * A thin helper around wohali/oauth2-discord-new.
 *
 * Usage:
 *   session_start();
 *   $auth = new DiscordAuth(
 *       clientId:     $_ENV['DISCORD_CLIENT_ID'],
 *       clientSecret: $_ENV['DISCORD_CLIENT_SECRET'],
 *       redirectUri:  'https://yoursite.com/auth/discord/callback',
 *   );
 *
 *   // Page 1 — send the user to Discord
 *   header('Location: ' . $auth->getAuthorizationUrl());
 *
 *   // Page 2 (callback) — exchange code for user
 *   $user = $auth->handleCallback($_GET['code'], $_GET['state']);
 *   echo $user->getUsername();
 */
class DiscordAuth
{
    private Discord $provider;

    /** Session key used to store the OAuth2 state nonce. */
    private string $stateSessionKey;

    /** Session key used to persist the serialised AccessToken. */
    private string $tokenSessionKey;

    /**
     * @param string   $clientId        Discord application client ID.
     * @param string   $clientSecret    Discord application client secret.
     * @param string   $redirectUri     Callback URL registered in your Discord app.
     * @param string[] $scopes          OAuth2 scopes to request (default: identify + email).
     * @param string   $stateSessionKey Session key for the CSRF state nonce.
     * @param string   $tokenSessionKey Session key for the stored access token.
     */
    public function __construct(
        string                 $clientId,
        string                 $clientSecret,
        string                 $redirectUri,
        private readonly array $scopes = ['identify', 'email'],
        string                 $stateSessionKey = 'discord_oauth2_state',
        string                 $tokenSessionKey = 'discord_oauth2_token',
    ) {
        $this->stateSessionKey = $stateSessionKey;
        $this->tokenSessionKey = $tokenSessionKey;

        $this->provider = new Discord([
            'clientId'     => $clientId,
            'clientSecret' => $clientSecret,
            'redirectUri'  => $redirectUri,
        ]);
    }

    // -------------------------------------------------------------------------
    // Step 1 – Build the redirect URL and store the CSRF state nonce
    // -------------------------------------------------------------------------

    /**
     * Returns the Discord authorization URL and saves the state nonce in the
     * session. Redirect the user to this URL to begin the OAuth2 flow.
     */
    public function getAuthorizationUrl(): string
    {
        $url = $this->provider->getAuthorizationUrl(['scope' => $this->scopes]);
        $_SESSION[$this->stateSessionKey] = $this->provider->getState();
        return $url;
    }

    // -------------------------------------------------------------------------
    // Step 2 – Handle the callback, return the Discord user
    // -------------------------------------------------------------------------

    /**
     * Validates the state nonce, exchanges the authorization code for an access
     * token, fetches the Discord user profile, and persists the token in the
     * session.
     *
     * @param string $code The `code` query parameter from the callback URL.
     * @param string $state The `state` query parameter from the callback URL.
     * @return DiscordResourceOwner
     *
     * @throws GuzzleException On API / token errors.
     * @throws IdentityProviderException On API / token errors.
     */
    public function handleCallback(string $code, string $state): DiscordResourceOwner
    {
        $this->validateState($state);

        $token = $this->provider->getAccessToken('authorization_code', [
            'code' => $code,
        ]);

        $this->storeToken($token);

        /** @var DiscordResourceOwner */
        return $this->provider->getResourceOwner($token);
    }

    // -------------------------------------------------------------------------
    // Token helpers
    // -------------------------------------------------------------------------

    /**
     * Returns the stored AccessToken from the session, automatically refreshing
     * it if it has expired (requires the offline_access / guilds scope etc.).
     * Returns null when no token has been stored yet.
     */
    public function getToken(): ?AccessToken
    {
        if (empty($_SESSION[$this->tokenSessionKey])) {
            return null;
        }

        $token = new AccessToken($_SESSION[$this->tokenSessionKey]);

        if ($token->hasExpired() && $token->getRefreshToken()) {
            $token = $this->refreshToken($token);
        }

        return $token;
    }

    /**
     * Uses the refresh token to obtain a new AccessToken and persists it.
     */
    public function refreshToken(AccessToken $token): AccessToken
    {
        $newToken = $this->provider->getAccessToken('refresh_token', [
            'refresh_token' => $token->getRefreshToken(),
        ]);

        $this->storeToken($newToken);

        return $newToken;
    }

    /**
     * Clears the stored token and state nonce from the session (logout).
     */
    public function clearSession(): void
    {
        unset($_SESSION[$this->stateSessionKey], $_SESSION[$this->tokenSessionKey]);
    }

    /**
     * Returns true when a (potentially expired) token exists in the session.
     * Does NOT guarantee the token is still valid with Discord's API.
     */
    public function isLoggedIn(): bool
    {
        return !empty($_SESSION[$this->tokenSessionKey]);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Validates the CSRF state nonce returned by Discord against what was
     * stored in the session.
     *
     * @throws RuntimeException On mismatch or missing state.
     */
    private function validateState(string $state): void
    {
        $stored = $_SESSION[$this->stateSessionKey] ?? null;
        unset($_SESSION[$this->stateSessionKey]);

        if (empty($stored) || $stored !== $state) {
            throw new RuntimeException(
                'OAuth2 state mismatch – possible CSRF attack. Please try logging in again.'
            );
        }
    }

    /**
     * Serialises an AccessToken into the session as a plain array so that it
     * survives across requests without storing object references.
     */
    private function storeToken(AccessToken $token): void
    {
        $_SESSION[$this->tokenSessionKey] = [
            'access_token'  => $token->getToken(),
            'refresh_token' => $token->getRefreshToken(),
            'expires'       => $token->getExpires(),
        ];
    }
}