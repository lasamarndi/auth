<?php
/**
 * SocialConnect project
 * @author: Patsura Dmitry https://github.com/ovr <talk@dmtry.me>
 */

namespace SocialConnect\Auth\Provider;

use SocialConnect\Auth\Provider\Exception\InvalidAccessToken;
use SocialConnect\Auth\Provider\Exception\InvalidResponse;
use SocialConnect\OAuth2\AccessToken;
use SocialConnect\Common\Entity\User;
use SocialConnect\Common\Hydrator\ObjectMap;

class Amazon extends \SocialConnect\OAuth2\AbstractProvider
{
    /**
     * {@inheritdoc}
     */
    public function getBaseUri()
    {
        return 'https://api.amazon.com/';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizeUri()
    {
        return 'https://www.amazon.com/ap/oa';
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestTokenUri()
    {
        return 'https://api.amazon.com/auth/o2/token';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'amazon';
    }

    /**
     * {@inheritdoc}
     */
    public function parseToken($body)
    {
        if (empty($body)) {
            throw new InvalidAccessToken('Provider response with empty body');
        }

        $result = json_decode($body);
        if ($result) {
            if (isset($result->access_token)) {
                return new AccessToken($result->access_token);
            }

            throw new InvalidAccessToken('Provider API returned without access_token field inside JSON');
        }

        throw new InvalidAccessToken('Provider response with not valid JSON');
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentity(AccessToken $accessToken)
    {
        $response = $this->service->getHttpClient()->request(
            $this->getBaseUri() . 'user/profile',
            [
                'access_token' => $accessToken->getToken()
            ]
        );

        if (!$response->isSuccess()) {
            throw new InvalidResponse(
                'API response with error code',
                $response
            );
        }

        $result = $response->json();
        if (!$result) {
            throw new InvalidResponse(
                'API response is not a valid JSON object',
                $response->getBody()
            );
        }

        $hydrator = new ObjectMap(array(
            'user_id' => 'id',
            'name' => 'firstname',
        ));

        return $hydrator->hydrate(new User(), $result);
    }
}