<?php

namespace Tranchard\CronMonitorApiBundle\Services\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;

class ApiSecretAuthenticator implements SimplePreAuthenticatorInterface, AuthenticationFailureHandlerInterface
{

    /**
     * @var null|string
     */
    private $secret;

    /**
     * ApiSecretAuthenticator constructor.
     *
     * @param null|string $secret
     */
    public function __construct(?string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * @inheritdoc
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new JsonResponse(
            [
                'success' => false,
                'message' => [
                    'code'    => JsonResponse::HTTP_UNAUTHORIZED,
                    'message' => $exception->getMessage(),
                    'trace'   => $exception->getTraceAsString(),
                ],
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        $apiSecret = $token->getCredentials();

        if ($apiSecret !== $this->secret) {
            throw new BadCredentialsException();
        }

        return new PreAuthenticatedToken(
            'cron-monitor',
            $apiSecret,
            $providerKey,
            ['ROLE_API']
        );
    }

    /**
     * @inheritdoc
     */
    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }

    /**
     * @inheritdoc
     */
    public function createToken(Request $request, $providerKey)
    {
        $apiSecret = $request->query->get('secret', $request->headers->get('secret'));

        if (!$apiSecret && !is_null($this->secret)) {
            throw new BadCredentialsException();
        }

        return new PreAuthenticatedToken('cron-monitor', $apiSecret, $providerKey);
    }
}
