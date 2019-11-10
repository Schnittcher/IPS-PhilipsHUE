<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/ColorHelper.php';

class HUEDevice extends IPSModule
{
    use ColorHelper;

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{6EFF1F3C-DF5F-43F7-DF44-F87EFF149566}');
        $this->RegisterPropertyString('HUEDeviceID', '');
        $this->RegisterPropertyString('DeviceType', '');
        $this->RegisterPropertyString('SensorType', '');

        $this->RegisterAttributeString('Scenes', '');

        if (!IPS_VariableProfileExists('HUE.ColorMode')) {
            IPS_CreateVariableProfile('HUE.ColorMode', 1);
        }
        IPS_SetVariableProfileAssociation('HUE.ColorMode', 0, $this->Translate('Color'), '', 0x000000);
        IPS_SetVariableProfileAssociation('HUE.ColorMode', 1, $this->Translate('Color Temperature'), '', 0x000000);
        IPS_SetVariableProfileIcon('HUE.ColorMode', 'ArrowRight');

        if (!IPS_VariableProfileExists('HUE.ColorMode')) {
            IPS_CreateVariableProfile('HUE.ColorMode', 1);
        }

        if (!IPS_VariableProfileExists('HUE.Reachable')) {
            IPS_CreateVariableProfile('HUE.Reachable', 0);
        }
        IPS_SetVariableProfileAssociation('HUE.Reachable', true, $this->Translate('Yes'), '', 0x00FF00);
        IPS_SetVariableProfileAssociation('HUE.Reachable', false, $this->Translate('No'), '', 0xFF0000);
        IPS_SetVariableProfileIcon('HUE.ColorMode', 'Information');

        if (!IPS_VariableProfileExists('HUE.ColorTemperature')) {
            IPS_CreateVariableProfile('HUE.ColorTemperature', 1);
        }
        IPS_SetVariableProfileDigits('HUE.ColorTemperature', 0);
        IPS_SetVariableProfileIcon('HUE.ColorTemperature', 'Bulb');
        IPS_SetVariableProfileText('HUE.ColorTemperature', '', ' Mired');
        IPS_SetVariableProfileValues('HUE.ColorTemperature', 153, 500, 1);

        if (!IPS_VariableProfileExists('HUE.Intensity')) {
            IPS_CreateVariableProfile('HUE.Intensity', 1);
        }
        IPS_SetVariableProfileDigits('HUE.Intensity', 0);
        IPS_SetVariableProfileIcon('HUE.Intensity', 'Intensity');
        IPS_SetVariableProfileText('HUE.Intensity', '', '%');
        //153 (6500K) to 500 (2000K)
        IPS_SetVariableProfileValues('HUE.Intensity', -1, 254, 1);

        $this->RegisterAttributeString('DeviceType', '');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        if ($this->ReadPropertyString('DeviceType') == '') {
            return;
        }

        //Scene Profile for Groups
        if ($this->HasActiveParent()) {
            $this->UpdateSceneProfile();
        } else {
            $ProfileName = 'HUE.GroupScene' . $this->ReadPropertyString('HUEDeviceID');
            if (!IPS_VariableProfileExists($ProfileName)) {
                IPS_CreateVariableProfile($ProfileName, 1);
            }
        }

        //Sensors
        $this->MaintainVariable('HUE_Battery', $this->Translate('Battery'), 1, '~Battery.100', 0, $this->ReadPropertyString('DeviceType') == 'sensors' && $this->ReadPropertyString('DeviceType') == 'sensors');

        $this->MaintainVariable('HUE_Presence', $this->Translate('Presence'), 0, '~Presence', 0, $this->ReadPropertyString('SensorType') == 'ZLLPresence' && $this->ReadPropertyString('DeviceType') == 'sensors');

        $this->MaintainVariable('HUE_Lightlevel', $this->Translate('Lightlevel'), 1, '~Illumination', 0, $this->ReadPropertyString('SensorType') == 'ZLLLightLevel' && $this->ReadPropertyString('DeviceType') == 'sensors');
        $this->MaintainVariable('HUE_Dark', $this->Translate('Dark'), 0, '', 0, $this->ReadPropertyString('SensorType') == 'ZLLLightLevel' && $this->ReadPropertyString('DeviceType') == 'sensors');
        $this->MaintainVariable('HUE_Daylight', $this->Translate('Daylight'), 0, '', 0, $this->ReadPropertyString('SensorType') == 'ZLLLightLevel' && $this->ReadPropertyString('DeviceType') == 'sensors');

        $this->MaintainVariable('HUE_Temperature', $this->Translate('Temperature'), 2, '~Temperature', 0, $this->ReadPropertyString('SensorType') == 'ZLLTemperature' && $this->ReadPropertyString('DeviceType') == 'sensors');

        $this->MaintainVariable('HUE_Buttonevent', $this->Translate('Buttonevent'), 1, '', 0, $this->ReadPropertyString('SensorType') == 'ZLLSwitch' && $this->ReadPropertyString('DeviceType') == 'sensors');

        //Lights and Groups
        $this->MaintainVariable('HUE_ColorMode', $this->Translate('Color Mode'), 1, 'HUE.ColorMode', 0, $this->ReadPropertyString('DeviceType') == 'lights' || $this->ReadPropertyString('DeviceType') == 'groups');
        $this->MaintainVariable('HUE_State', $this->Translate('State'), 0, '~Switch', 0, $this->ReadPropertyString('DeviceType') == 'lights' || $this->ReadPropertyString('DeviceType') == 'groups');
        $this->MaintainVariable('HUE_Brightness', $this->Translate('Brightness'), 1, 'HUE.Intensity', 0, $this->ReadPropertyString('DeviceType') == 'lights' || $this->ReadPropertyString('DeviceType') == 'groups');
        $this->MaintainVariable('HUE_Color', $this->Translate('Color'), 1, 'HexColor', 0, $this->ReadPropertyString('DeviceType') == 'lights' || $this->ReadPropertyString('DeviceType') == 'groups');
        $this->MaintainVariable('HUE_Saturation', $this->Translate('Saturation'), 1, 'HUE.Intensity', 0, $this->ReadPropertyString('DeviceType') == 'lights' || $this->ReadPropertyString('DeviceType') == 'groups');
        $this->MaintainVariable('HUE_ColorTemperature', $this->Translate('Color Temperature'), 1, 'HUE.ColorTemperature', 0, $this->ReadPropertyString('DeviceType') == 'lights' || $this->ReadPropertyString('DeviceType') == 'groups');

        //Groups
        $this->MaintainVariable('HUE_GroupScenes', $this->Translate('Scenes'), 1, 'HUE.GroupScene' . $this->ReadPropertyString('HUEDeviceID'), 0, $this->ReadPropertyString('DeviceType') == 'groups');
        if ($this->ReadPropertyString('DeviceType') == 'lights' || $this->ReadPropertyString('DeviceType') == 'groups') {
            $this->EnableAction('HUE_ColorMode');
            $this->EnableAction('HUE_State');
            $this->EnableAction('HUE_Brightness');
            $this->EnableAction('HUE_Color');
            $this->EnableAction('HUE_Saturation');
            $this->EnableAction('HUE_ColorTemperature');
            $ColorMode = GetValue(IPS_GetObjectIDByIdent('HUE_ColorMode', $this->InstanceID));
            $this->hideVariables($ColorMode);
        }

        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            SetValue($this->GetIDForIdent('HUE_GroupScenes'), -1);
            $this->EnableAction('HUE_GroupScenes');
        }

        //Reachable for Lights and Sensors
        if ($this->ReadPropertyString('DeviceType') == 'lights' || ($this->ReadPropertyString('DeviceType') == 'sensors' && $this->ReadPropertyString('SensorType') != 'ZGPSwitch' && $this->ReadPropertyString('SensorType') != 'Daylight')) {
            $CreateVariableReachable = true;
        } else {
            $CreateVariableReachable = false;
        }
        $this->MaintainVariable('HUE_Reachable', $this->Translate('Reachable'), 0, 'HUE.Reachable', 0, $CreateVariableReachable);
    }

    public function GetConfigurationForm()
    {
        $jsonForm = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        if ($this->ReadAttributeString('DeviceType') == 'sensors') {
            $jsonForm['elements'][2]['visible'] = true;
        } else {
            $jsonForm['elements'][2]['visible'] = false;
        }
        return json_encode($jsonForm);
    }

    public function ReceiveData($JSONString)
    {
        $this->SendDebug(__FUNCTION__ . ' Device Type', $this->ReadPropertyString('DeviceType'), 0);
        $this->SendDebug(__FUNCTION__ . ' Device ID', $this->ReadPropertyString('HUEDeviceID'), 0);
        $this->SendDebug(__FUNCTION__, $JSONString, 0);
        $Data = json_decode($JSONString);
        $Buffer = json_decode($Data->Buffer);

        $this->SendDebug(__FUNCTION__ . ' Data Buffer', $Data->Buffer, 0);

        $DeviceConfig = new stdClass();

        switch ($this->ReadPropertyString('DeviceType')) {
            case 'groups':
                if (property_exists($Buffer->Groups, $this->ReadPropertyString('HUEDeviceID'))) {
                    if (property_exists($Buffer->Groups->{$this->ReadPropertyString('HUEDeviceID')}, 'action')) {
                        $DeviceState = $Buffer->Groups->{$this->ReadPropertyString('HUEDeviceID')}->action;
                    }
                } else {
                    IPS_LogMessage('Philips HUE', 'Group Device ID: ' . $this->ReadPropertyString('HUEDeviceID') . ' invalid');
                    return;
                }
                break;

            case 'lights':
                if (property_exists($Buffer->Lights, $this->ReadPropertyString('HUEDeviceID'))) {
                    if (property_exists($Buffer->Lights->{$this->ReadPropertyString('HUEDeviceID')}, 'state')) {
                        $DeviceState = $Buffer->Lights->{$this->ReadPropertyString('HUEDeviceID')}->state;
                    }
                    if (property_exists($Buffer->Lights->{$this->ReadPropertyString('HUEDeviceID')}, 'config')) {
                        $DeviceConfig = $Buffer->Lights->{$this->ReadPropertyString('HUEDeviceID')}->config;
                    }
                } else {
                    IPS_LogMessage('Philips HUE', 'Device ID: ' . $this->ReadPropertyString('HUEDeviceID') . ' invalid');
                    return;
                }
                break;

            case 'sensors':
                if (property_exists($Buffer->Sensors, $this->ReadPropertyString('HUEDeviceID'))) {
                    if (property_exists($Buffer->Sensors->{$this->ReadPropertyString('HUEDeviceID')}, 'state')) {
                        $DeviceState = $Buffer->Sensors->{$this->ReadPropertyString('HUEDeviceID')}->state;
                    }
                    if (property_exists($Buffer->Sensors->{$this->ReadPropertyString('HUEDeviceID')}, 'config')) {
                        $DeviceConfig = $Buffer->Sensors->{$this->ReadPropertyString('HUEDeviceID')}->config;
                    }
                } else {
                    IPS_LogMessage('Philips HUE', 'Device ID: ' . $this->ReadPropertyString('HUEDeviceID') . ' invalid');
                    return;
                }
                break;

            default:
                $this->SendDebug(__FUNCTION__, 'Invalid Device Type', 0);
                return;
        }

        //Convert XY to RGB an set Color if Color Lamp
        if (property_exists($DeviceState, 'xy')) {
            if ($DeviceState->bri == 0) {
                $brightness = 1;
            } else {
                $brightness = $DeviceState->bri;
            }
            $RGB = $this->convertXYToRGB($DeviceState->xy[0], $DeviceState->xy[1], $brightness);
            $Color = $RGB['red'] * 256 * 256 + $RGB['green'] * 256 + $RGB['blue'];
            $this->SetValue('HUE_Color', $Color);
        }
        if (property_exists($DeviceState, 'on')) {
            $this->SetValue('HUE_State', $DeviceState->on);
        }
        if (property_exists($DeviceState, 'bri')) {
            if ($DeviceState->on) {
                if ($DeviceState->bri == 0) {
                    $this->SetValue('HUE_Brightness', 1);
                } else {
                    $this->SetValue('HUE_Brightness', $DeviceState->bri);
                }
            } else {
                $this->SetValue('HUE_Brightness', -1);
            }
        }
        if (property_exists($DeviceState, 'sat')) {
            $this->SetValue('HUE_Saturation', $DeviceState->sat);
        }
        if (property_exists($DeviceState, 'ct')) {
            $this->SetValue('HUE_ColorTemperature', $DeviceState->ct);
        }
        if (property_exists($DeviceState, 'colormode')) {
            switch ($DeviceState->colormode) {
                case 'xy':
                    $this->SetValue('HUE_ColorMode', 0);
                    break;
                case 'hs':
                    $this->SetValue('HUE_ColorMode', 0);
                    break;
                case 'ct':
                    $this->SetValue('HUE_ColorMode', 1);
                    break;
                default:
                    IPS_LogMessage('Philips HUE', 'Invalid ColorMode: ' . $DeviceState->colormode);
                    break;
            }
        }

        if (property_exists($DeviceState, 'presence')) {
            $this->SetValue('HUE_Presence', $DeviceState->presence);
        }
        if (property_exists($DeviceConfig, 'battery')) {
            $this->SetValue('HUE_Battery', $DeviceConfig->battery);
        }
        if (property_exists($DeviceConfig, 'reachable')) {
            $this->SetValue('HUE_Reachable', $DeviceConfig->reachable);
        }
        if (property_exists($DeviceState, 'reachable')) {
            $this->SetValue('HUE_Reachable', $DeviceState->reachable);
        }
        if (property_exists($DeviceState, 'lightlevel')) {
            $this->SetValue('HUE_Lightlevel', intval(pow(10, $DeviceState->lightlevel / 10000)));
        }
        if (property_exists($DeviceState, 'dark')) {
            $this->SetValue('HUE_Dark', $DeviceState->dark);
        }
        if (property_exists($DeviceState, 'daylight')) {
            $this->SetValue('HUE_Daylight', $DeviceState->daylight);
        }
        if (property_exists($DeviceState, 'temperature')) {
            $this->SetValue('HUE_Temperature', $DeviceState->temperature / 100);
        }
        if (property_exists($DeviceState, 'buttonevent')) {
            $this->SetValue('HUE_Buttonevent', $DeviceState->buttonevent);
        }
    }

    public function Request(array $Value)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }
        return $this->sendData($command, $Value);
    }

    public function SwitchMode(bool $Value)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        $params = ['on' => $Value];
        return $this->sendData($command, $params);
    }

    public function DimSet(int $Value)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        if ($Value <= 0) {
            $params = ['on' => false];
        } else {
            $params = ['bri' => $Value, 'on' => true];
        }
        return $this->sendData($command, $params);
    }

    public function ColorSet(variant $Value)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        //If $Value Hex Color convert to Decimal
        if (preg_match('/^#[a-f0-9]{6}$/i', strval($Value))) {
            $Value = hexdec($Value);
        }

        $this->SendDebug(__FUNCTION__, $Value, 0);

        $rgb = $this->decToRGB($Value);

        $ConvertedXY = $this->convertRGBToXY($rgb['r'], $rgb['g'], $rgb['b']);
        $xy[0] = $ConvertedXY['x'];
        $xy[1] = $ConvertedXY['y'];

        $params = ['bri' => $ConvertedXY['bri'], 'xy' => $xy, 'on' => true];

        return $this->sendData($command, $params);
    }

    public function ColorSetOpt(variant $Value, array $OptParams = null)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        //If $Value Hex Color convert to Decimal
        if (preg_match('/^#[a-f0-9]{6}$/i', strval($Value))) {
            $Value = hexdec($Value);
        }

        $this->SendDebug(__FUNCTION__, $Value, 0);

        $rgb = $this->decToRGB($Value);

        $ConvertedXY = $this->convertRGBToXY($rgb['r'], $rgb['g'], $rgb['b']);
        $xy[0] = $ConvertedXY['x'];
        $xy[1] = $ConvertedXY['y'];

        $params = ['bri' => $ConvertedXY['bri'], 'xy' => $xy, 'on' => true];
        $params = array_merge($params, $OptParams);
        return $this->sendData($command, $params);
    }

    public function SatSet(int $Value)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        $params = ['sat' => $Value];
        return $this->sendData($command, $params);
    }

    public function CTSet(int $Value)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        $params = ['ct' => $Value];
        return $this->sendData($command, $params);
    }

    public function SceneSet(string $Value)
    {
        $scenes = json_decode($this->ReadAttributeString('Scenes'), true);
        foreach ($scenes as $key => $scene) {
            if ($scene['name'] == $Value) {
                $this->SceneSetKey($scene['key']);
                return;
            }
        }
        IPS_LogMessage('Philips HUE', 'Scene Name (' . $Value . ') for Group ' . $this->ReadPropertyString('HUEDeviceID') . ' invalid');
    }

    public function AlertSet(string $Value)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        $params = ['alert' => $Value];
        return $this->sendData($command, $params);
    }

    public function EffectSet(string $Value)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        $params = ['effect' => $Value];
        return $this->sendData($command, $params);
    }

    public function GetState()
    {
        $params = [];
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'getGroupState';
            $result = $this->sendData($command, $params)['action']['on'];
        } else {
            $command = 'getLightState';
            $result = $this->sendData($command, $params)['state']['on'];
        }
        return $result;
    }

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'HUE_State':
                $result = $this->SwitchMode($Value);
                if (array_key_exists('success', $result[0])) {
                    $this->SetValue($Ident, $Value);
                }
                break;
            case 'HUE_Brightness':
                $result = $this->DimSet($Value);

                if ($Value <= 0) {
                    $this->SwitchMode(false);
                    $this->SetValue('HUE_State', false);
                    return;
                }
                if (array_key_exists('success', $result[0])) {
                    $this->SetValue('HUE_State', true);
                }
                if (array_key_exists('success', $result[1])) {
                    $this->SetValue($Ident, $Value);
                }
                break;
            case 'HUE_Color':
                $result = $this->ColorSet($Value);
                if (array_key_exists('success', $result[0])) {
                    $this->SetValue('HUE_State', true);
                }

                if ($this->ReadPropertyString('DeviceType') == 'groups') {
                    //If DeviceType Group Key 1 is Brightness
                    if (array_key_exists('success', $result[1])) {
                        $this->SetValue('HUE_Brightness', $result[1]['success']['/groups/' . $this->ReadPropertyString('HUEDeviceID') . '/action/bri']);
                    }
                    //If DeviceType is Group Key 2 is Color
                    if (array_key_exists('success', $result[2])) {
                        $this->SetValue($Ident, $Value);
                    }
                } elseif ($this->ReadPropertyString('DeviceType') == 'lights') {
                    //If DeviceType is Lights Key 1 is Color
                    if (array_key_exists('success', $result[1])) {
                        $this->SetValue($Ident, $Value);
                    }
                    //If DeviceType is Lights Key 2 is Brightness
                    if (array_key_exists('success', $result[2])) {
                        $this->SetValue('HUE_Brightness', $result[2]['success']['/lights/' . $this->ReadPropertyString('HUEDeviceID') . '/state/bri']);
                    }
                }
                break;
            case 'HUE_Saturation':
                $result = $this->SatSet($Value);

                if (array_key_exists('success', $result[0])) {
                    $this->SetValue($Ident, $Value);
                }
                break;
            case 'HUE_ColorTemperature':
                $result = $this->CTSet($Value);
                if (array_key_exists('success', $result[0])) {
                    $this->SetValue($Ident, $Value);
                }
                break;
            case 'HUE_ColorMode':
                $this->hideVariables($Value);
                switch ($Value) {
                    case 0: //Color
                        $result = $this->ColorSet($this->GetValue('HUE_Color'));
                        if (array_key_exists('success', $result[0])) {
                            $this->SetValue('HUE_State', true);
                        }
                        break;
                    case 1: //Color temperature
                        $result = $this->CTSet($this->GetValue('HUE_ColorTemperature'));
                        if (array_key_exists('success', $result[0])) {
                            $this->SetValue($Ident, $Value);
                        }
                }
                $this->SetValue($Ident, $Value);
                break;
            case 'HUE_GroupScenes':
                $scenes = json_decode($this->ReadAttributeString('Scenes'), true);
                $this->SendDebug(__FUNCTION__ . ' Scene Value', $scenes[$Value]['name'], 0);
                $this->SceneSetKey($scenes[$Value]['key']);
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'Invalid Ident', 0);
                break;
        }
    }

    public function ReloadConfigurationFormDeviceType($DeviceType)
    {
        $this->WriteAttributeString('DeviceType', $DeviceType);
        if ($DeviceType == 'sensors') {
            $this->UpdateFormField('SensorType', 'visible', true);
        } else {
            $this->UpdateFormField('SensorType', 'visible', false);
        }
    }

    public function UpdateSceneProfile()
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            //TODO Map Profile to Attribute
            $scenes = $this->sendData('getScenesFromGroup', ['GroupID' => $this->ReadPropertyString('HUEDeviceID')]);
            $ProfileName = 'HUE.GroupScene' . $this->ReadPropertyString('HUEDeviceID');
            if (!IPS_VariableProfileExists($ProfileName)) {
                IPS_CreateVariableProfile($ProfileName, 1);
            } else {
                if (!empty($scenes)) {
                    IPS_DeleteVariableProfile($ProfileName);
                    IPS_CreateVariableProfile($ProfileName, 1);
                }
            }

            $scenesAttribute = [];
            //$this->WriteAttributeString('Scenes',json_encode($scenes));
            $countScene = 0;
            foreach ($scenes as $key => $scene) {
                IPS_SetVariableProfileAssociation($ProfileName, $countScene, $scene['name'], '', 0x000000);
                $scenesAttribute[$countScene]['name'] = $scene['name'];
                $scenesAttribute[$countScene]['key'] = $key;
                $countScene++;
            }
            IPS_SetVariableProfileIcon($ProfileName, 'Database');
            if (!empty($scenesAttribute)) {
                $this->WriteAttributeString('Scenes', json_encode($scenesAttribute));
            }
        }
    }

    private function SceneSetKey(string $Value)
    {
        $params = ['scene' => $Value];
        return $this->sendData('action', $params);
    }

    private function hideVariables($Value)
    {
        switch ($Value) {
            case 0:
                IPS_SetHidden($this->GetIDForIdent('HUE_Saturation'), true);
                IPS_SetHidden($this->GetIDForIdent('HUE_ColorTemperature'), true);

                IPS_SetHidden($this->GetIDForIdent('HUE_Color'), false);
                break;
            case 1:
                IPS_SetHidden($this->GetIDForIdent('HUE_Color'), true);

                IPS_SetHidden($this->GetIDForIdent('HUE_Saturation'), false);
                IPS_SetHidden($this->GetIDForIdent('HUE_ColorTemperature'), false);
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'Invalid Color Mode: ' . $Value, 0);
                break;
        }
    }

    private function sendData(string $command, $params = '')
    {
        $Data['DataID'] = '{03995C27-F41C-4E0C-85C9-099084294C3B}';
        $Buffer['Command'] = $command;
        $Buffer['DeviceID'] = $this->ReadPropertyString('HUEDeviceID');
        $Buffer['Endpoint'] = $this->ReadPropertyString('DeviceType');
        $Buffer['Params'] = $params;
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);

        if (!$this->HasActiveParent()) {
            return [];
        }

        $this->SendDebug(__FUNCTION__, $Data, 0);
        $result = $this->SendDataToParent($Data);
        $this->SendDebug(__FUNCTION__, $result, 0);

        if (!$result) {
            return [];
        }
        $Data = json_decode($result, true);
        return $Data;
    }
}
