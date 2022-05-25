<?php

namespace Dominiquevienne\LaravelMagic\Middleware;

use Dominiquevienne\LaravelMagic\Exceptions\EnvException;
use Closure;
use Exception;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class VerifyJwtToken
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return JsonResponse|Response|RedirectResponse
     * @throws EnvException
     */
    public function handle(Request $request, Closure $next)
    {
        $jwtToken = $this->getJwtToken($request);
        $publicKey = env('OAUTH_KEY_PUB');

        if (empty($publicKey)) {
            throw new EnvException(__('Missing OAUTH_KEY_PUB'));
        }

        if (empty($jwtToken)) {
            /**
             * @todo Use a formatted response
             */
            return \response(__('Forbidden'), 403);
        }

        $visibleLength = 20;
        $anonymizedToken = substr($jwtToken, 0, $visibleLength) . '...' . substr($jwtToken, strlen($jwtToken) - $visibleLength, $visibleLength);

        try {
            $tokenDecoded = JWT::decode($jwtToken, new Key($publicKey, 'RS256'));
            Session::put('token_decoded', (array) $tokenDecoded);
        } catch (ExpiredException $e) {

            Log::info(__DIR__ . DIRECTORY_SEPARATOR . __FILE__ . ' - ' . $e->getMessage() . ' - token: ' . $anonymizedToken);

            /**
             * @todo Use a formatted response
             */
            return \response(__('Token expired'), 403);
        } catch (Exception $e) {
            /** Log info in logging and not log user in */
            Log::warning(__DIR__ . DIRECTORY_SEPARATOR . __FILE__ . ' - ' . $e->getMessage() . ' - token: ' . $anonymizedToken);

            /**
             * @todo Use a formatted response
             */
            return \response(__('Invalid token'), 403);
        }

        return $next($request);
    }

    /**
     * @param Request $request
     * @return string|null
     */
    private function getJwtToken(Request $request): ?string
    {
        $authorizationHeader = $request->header('Authorization');

        return preg_replace('/^Bearer /si', '', $authorizationHeader);
    }
}
