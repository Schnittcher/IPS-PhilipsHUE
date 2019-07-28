<?php

declare(strict_types=1);

class IPS_Shelly1 extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterPropertyString('Host', '');
        $this->RegisterPropertyInteger('UpdateInterval', 60);

        $this->RegisterAttributeString('User','');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
    }


    private function sendRequest(string $User = '', string $endpoint, array $params = array(), string $method = 'GET')
    {
        $ch = curl_init();
        $this->SendDebug(__FUNCTION__ . ' URL', $this->ReadPropertyString('Host') . '/api/' . $endpoint, 0);
        if ($User != '') {
            curl_setopt($ch, CURLOPT_URL, $this->ReadPropertyString('Host') . '/api/' . $endpoint);
        } else {
            curl_setopt($ch, CURLOPT_URL, $this->ReadPropertyString('Host') . '/api/'.$User.'/'. $endpoint);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, 'Symcon');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST' || $method == 'PUT' || $method == 'DELETE') {
            if ($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
            }
            if (in_array($method, array('PUT', 'DELETE'))) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        $apiResult = curl_exec($ch);
        $headerInfo = curl_getinfo($ch);
        $apiResultJson = json_decode($apiResult, true);
        curl_close($ch);
    }

    private function registerUser()
    {
        $params['devicetype'] = 'Symcon';
        $result = $this->sendRequest('', $params, 'POST');

        if (@isset($result[0]->success->username) && $result[0]->success->username != '') {
            $this->WriteAttributeString('User', $result[0]->success->username);
        } else {
            IPS_LogMessage('PhilipsHUE', 'Register User failed');
        }
    }

    //Functions for Lights

    private function getAllLights() {
        return $this->sendRequest($this->ReadAttributeString(),'lights', [], 'GET');
    }

    private function getNewLights() {
        return $this->sendRequest($this->ReadAttributeString(),'lights/new', [], 'GET');
    }

    private function scanNewLights() {
        return $this->sendRequest($this->ReadAttributeString(),'lights', [], 'POST');
    }

    private function getLight($id) {
        return $this->sendRequest($this->ReadAttributeString(),'lights/'.$id, [], 'GET');
    }

    private function renameLight($id,$name) {
        $params['name'] = $name;
        return $this->sendRequest($this->ReadAttributeString(),'lights/'.$id, $params, 'PUT');
    }

    private function setLightState($id,$state) {
        return $this->sendRequest($this->ReadAttributeString(),'lights/'.$id, $state, 'PUT');
    }

    private function deleteLight($id) {
        return $this->sendRequest($this->ReadAttributeString(),'lights/'.$id, [], 'DELETE');
    }

    //Functions for Groups


    private function getAllGroups() {
        return $this->sendRequest($this->ReadAttributeString(),'groups', [], 'GET');
    }

    //Functions for Schedules

    private function getAllSchedules() {
        return $this->sendRequest($this->ReadAttributeString(),'schedules', [], 'GET');
    }

    //Functions for Scenes

    private function getAllScenes() {
        return $this->sendRequest($this->ReadAttributeString(),'scenes', [], 'GET');
    }

    private function getAllSensors() {
        return $this->sendRequest($this->ReadAttributeString(),'sensors', [], 'GET');
    }

    //Functions for Rules

    private function getAllRules() {
        return $this->sendRequest($this->ReadAttributeString(),'rules', $params, 'GET');
    }

}