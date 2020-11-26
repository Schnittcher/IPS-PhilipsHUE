<?php

declare(strict_types=1);

trait PHUE_hueAPI
{
    #################### Public

    /**
     * Gets the attributes and the state of a light.
     *
     * @param $ID
     * @return array|false|mixed|stdClass
     */
    public function Bridge_GetLightAttributesAndState(int $ID)
    {
        $user = $this->ReadAttributeString('User');
        $endpoint = 'lights/' . $ID;
        return $this->sendRequest($user, $endpoint, [], 'GET');
    }

    /**
     * Sets the state of a light.
     *
     * @param int $ID
     * @param array $Arguments
     * @return array|false|mixed|stdClass
     */
    public function Bridge_SetLightState(int $ID, array $Arguments)
    {
        $user = $this->ReadAttributeString('User');
        $endpoint = 'lights/' . $ID . '/state';
        return $this->sendRequest($user, $endpoint, $Arguments, 'PUT');
    }
}