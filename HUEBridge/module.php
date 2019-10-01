<?php

declare(strict_types=1);

class HUEBridge extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterPropertyString('Host', '');
        $this->RegisterPropertyInteger('UpdateInterval', 10);

        $this->RegisterTimer('PHUE_UpdateState', 0, 'PHUE_UpdateState($_IPS[\'TARGET\']);');
        $this->RegisterAttributeString('User', '');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        if (!$this->BridgePaired()) {
            $this->SetStatus(200);
            IPS_LogMessage('PhilipsHUE', 'Error: Registration incomplete, please pair IP-Symcon with the Philips HUE Bridge.');
            $this->SetTimerInterval('PHUE_UpdateState', 0);
            return;
        }
        $this->SetTimerInterval('PHUE_UpdateState', $this->ReadPropertyInteger('UpdateInterval') * 1000);
    }

    public function ForwardData($JSONString)
    {
        $this->SendDebug(__FUNCTION__, $JSONString, 0);
        $data = json_decode($JSONString);
        switch ($data->Buffer->Command) {
            case 'getAllLights':
                $result = $this->getAllLights();
                break;
            case 'getAllGroups':
                $result = $this->getAllGroups();
                break;
            case 'getAllSensors':
                $result = $this->getAllSensors();
                break;
            case 'getScenesFromGroup':
                $params = (array) $data->Buffer->Params;
                $result = $this->getAlleScenesFromGroup($params['GroupID']);
                break;
            case 'state':
                $params = (array) $data->Buffer->Params;
                $result = $this->sendRequest($this->ReadAttributeString('User'), $data->Buffer->Endpoint . '/' . $data->Buffer->DeviceID . '/state', $params, 'PUT');
                break;
            case 'action':
                $params = (array) $data->Buffer->Params;
                $result = $this->sendRequest($this->ReadAttributeString('User'), $data->Buffer->Endpoint . '/' . $data->Buffer->DeviceID . '/action', $params, 'PUT');
                break;
            case 'scanNewDevices':
                $result = $this->scanNewLights();
                break;
            case 'getNewDevices':
                $result = $this->getNewLights();
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'Invalid Command: ' . $data->Buffer->Command, 0);
                break;
        }
        $this->SendDebug(__FUNCTION__, json_encode($result), 0);
        return json_encode($result);
    }

    private function sendRequest(string $User, string $endpoint, array $params = [], string $method = 'GET')
    {
        if ($this->ReadPropertyString('Host') == '') {
            return false;
        }

        $this->SendDebug('User', $User, 0);
        $ch = curl_init();
        if ($User != '' && $endpoint != '') {
            $this->SendDebug(__FUNCTION__ . ' URL', $this->ReadPropertyString('Host') . '/api/' . $User . '/' . $endpoint, 0);
            curl_setopt($ch, CURLOPT_URL, $this->ReadPropertyString('Host') . '/api/' . $User . '/' . $endpoint);
        } elseif ($endpoint != '') {
            return [];
        } else {
            $this->SendDebug(__FUNCTION__ . ' URL', $this->ReadPropertyString('Host') . '/api/' . $endpoint, 0);
            curl_setopt($ch, CURLOPT_URL, $this->ReadPropertyString('Host') . '/api/' . $endpoint);
        }

        curl_setopt($ch, CURLOPT_USERAGENT, 'Symcon');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method == 'POST' || $method == 'PUT' || $method == 'DELETE') {
            if ($method == 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
            }
            if (in_array($method, ['PUT', 'DELETE'])) {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        $apiResult = curl_exec($ch);
        $this->SendDebug(__FUNCTION__ . ' Result', $apiResult, 0);
        $headerInfo = curl_getinfo($ch);
        curl_close($ch);
        $result = json_decode($apiResult, false);
        return $result;
    }

    public function UpdateState()
    {
        $Data['DataID'] = '{6C33FAE0-8FF8-4CAE-B5E9-89A2D24D067D}';

        $Buffer['Lights'] = $this->getAllLights();
        $Buffer['Groups'] = $this->getAllGroups();
        $Buffer['Sensors'] = $this->getAllSensors();

        $Data['Buffer'] = json_encode($Buffer);

        $Data = json_encode($Data);
        $this->SendDataToChildren($Data);
    }

    public function registerUser()
    {
        $params['devicetype'] = 'Symcon';
        $result = $this->sendRequest('', '', $params, 'POST');
        if (@isset($result[0]->success->username)) {
            $this->SendDebug('Register User', 'OK: ' . $result[0]->success->username, 0);
            $this->WriteAttributeString('User', $result[0]->success->username);
            $this->SetStatus(102);
        } else {
            $this->SendDebug(__FUNCTION__ . 'Pairing failed', json_encode($result), 0);
            $this->SetStatus(200);
            IPS_LogMessage('PhilipsHUE - User registration', 'Error: ' . $result[0]->error->type . ': ' . $result[0]->error->description);
        }
    }

    //Functions for Lights

    public function getAllLights()
    {
        return $this->sendRequest($this->ReadAttributeString('User'), 'lights', [], 'GET');
    }

    private function getNewLights()
    {
        return $this->sendRequest($this->ReadAttributeString('User'), 'lights/new', [], 'GET');
    }

    private function scanNewLights()
    {
        $params['deviceid'] = [];
        return $this->sendRequest($this->ReadAttributeString('User'), 'lights', $params, 'POST');
    }

    private function getLight($id)
    {
        return $this->sendRequest($this->ReadAttributeString('User'), 'lights/' . $id, [], 'GET');
    }

    private function renameLight($id, $name)
    {
        $params['name'] = $name;
        return $this->sendRequest($this->ReadAttributeString('User'), 'lights/' . $id, $params, 'PUT');
    }

    private function setLightState($id, $state)
    {
        return $this->sendRequest($this->ReadAttributeString('User'), 'lights/' . $id, $state, 'PUT');
    }

    private function deleteLight($id)
    {
        return $this->sendRequest($this->ReadAttributeString('User'), 'lights/' . $id, [], 'DELETE');
    }

    //Functions for Sensors

    private function getAllSensors()
    {
        return $this->sendRequest($this->ReadAttributeString('User'), 'sensors', [], 'GET');
    }

    //Functions for Groups

    private function getAllGroups()
    {
        return $this->sendRequest($this->ReadAttributeString('User'), 'groups', [], 'GET');
    }

    //Functions for Schedules

    private function getAllSchedules()
    {
        return $this->sendRequest($this->ReadAttributeString('User'), 'schedules', [], 'GET');
    }

    //Functions for Scenes

    public function getAllScenes()
    {
        return $this->sendRequest($this->ReadAttributeString('User'), 'scenes', [], 'GET');
    }

    private function getAlleScenesFromGroup($GroupID)
    {
        $AllScenes = $this->getAllScenes();
        $GroupScenes = [];

        foreach ($AllScenes as $key => $scene) {
            if ($scene->type == 'GroupScene') {
                if ($scene->group == $GroupID) {
                    $GroupScenes[$key] = $scene;
                }
            }
        }
        return $GroupScenes;
    }

    //Functions for Rules

    private function getAllRules()
    {
        return $this->sendRequest($this->ReadAttributeString('User'), 'rules', $params, 'GET');
    }

    private function BridgePaired()
    {
        if ($this->ReadAttributeString('User') != '') {
            return true;
        }
        return false;
    }
}
