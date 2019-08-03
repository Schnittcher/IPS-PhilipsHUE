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
    }

    public function ApplyChanges()
    {

        //Never delete this line!
        parent::ApplyChanges();
        if ($this->ReadPropertyString('DeviceType') == '') {
            return;
        }
        if ($this->ReadPropertyString('DeviceType') == 'sensors') {
            switch ($this->ReadPropertyString('SensorType')) {
                case 'Daylight':
                    $this->SendDebug(__FUNCTION__ . $this->ReadPropertyString('DeviceType'), 'To do', 0);
                    break;
                case 'ZLLPresence':
                    $this->RegisterVariableBoolean('HUE_Presence', $this->Translate('Presence'), '~Presence');
                    $this->RegisterVariableInteger('HUE_Battery', $this->Translate('Battery'), '~Battery.100');
                    break;
                case 'ZLLLightLevel':
                    $this->RegisterVariableInteger('HUE_Lightlevel', $this->Translate('Lightlevel'), '');
                    $this->RegisterVariableBoolean('HUE_Dark', $this->Translate('Dark'), '');
                    $this->RegisterVariableBoolean('HUE_Daylight', $this->Translate('Daylight'), '');
                    $this->RegisterVariableInteger('HUE_Battery', $this->Translate('Battery'), '~Battery.100');
                    break;
                case 'ZLLTemperature':
                    $this->RegisterVariableFloat('HUE_Temperature', $this->Translate('Temperature'), '');
                    $this->RegisterVariableInteger('HUE_Battery', $this->Translate('Battery'), '~Battery.100');
                    break;
                case 'ZLLSwitch':
                    $this->RegisterVariableFloat('HUE_Buttonevent', $this->Translate('Buttonevent'), '');
                    $this->RegisterVariableInteger('HUE_Battery', $this->Translate('Battery'), '~Battery.100');
                    break;
                default:
                    $this->SendDebug(__FUNCTION__ . ' Sensor Type', $this->ReadPropertyString('DeviceType'), 0);
            }
        } else {
            $this->RegisterVariableBoolean('HUE_State', $this->Translate('State'), '~Switch');
            $this->RegisterVariableInteger('HUE_Brightness', $this->Translate('Brightness'), '~Intensity.255');
            $this->RegisterVariableInteger('HUE_Color', $this->Translate('Color'), 'HexColor');

            $this->EnableAction('HUE_State');
            $this->EnableAction('HUE_Brightness');
            $this->EnableAction('HUE_Color');
        }
    }

    public function ReceiveData($JSONString)
    {
        $this->SendDebug(__FUNCTION__ . ' Device Type', $this->ReadPropertyString('DeviceType'), 0);
        $this->SendDebug(__FUNCTION__ . ' Device ID', $this->ReadPropertyString('HUEDeviceID'), 0);
        $this->SendDebug(__FUNCTION__, $JSONString, 0);
        $Data = json_decode($JSONString);
        $Buffer = json_decode($Data->Buffer);

        $DeviceConfig = new stdClass();

        switch ($this->ReadPropertyString('DeviceType')) {
            case 'groups':
                if (property_exists($Buffer->Groups->{$this->ReadPropertyString('HUEDeviceID')}, 'action')) {
                    $DeviceState = $Buffer->Groups->{$this->ReadPropertyString('HUEDeviceID')}->action;
                    //$DeviceConfig = $Buffer->Groups->{$this->ReadPropertyString('HUEDeviceID')}->config;
                }
                break;
            case 'lights':
                if (property_exists($Buffer->Lights->{$this->ReadPropertyString('HUEDeviceID')}, 'state')) {
                    $DeviceState = $Buffer->Lights->{$this->ReadPropertyString('HUEDeviceID')}->state;
                    $DeviceConfig = $Buffer->Lights->{$this->ReadPropertyString('HUEDeviceID')}->config;
                }
                break;
            case 'sensors':
                if (property_exists($Buffer->Sensors->{$this->ReadPropertyString('HUEDeviceID')}, 'state')) {
                    $DeviceState = $Buffer->Sensors->{$this->ReadPropertyString('HUEDeviceID')}->state;
                    $DeviceConfig = $Buffer->Sensors->{$this->ReadPropertyString('HUEDeviceID')}->config;
                }
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'Invalid Device Type', 0);
                return;
        }

        //Convervt XY to RGB an set Color if Color Lamp
        if (property_exists($DeviceState, 'xy')) {
            $RGB = $this->convertXYToRGB($DeviceState->xy[0], $DeviceState->xy[1], $DeviceState->bri);
            $Color = $RGB['red'] * 256 * 256 + $RGB['green'] * 256 + $RGB['blue'];
            $this->SetValue('HUE_Color', $Color);
        }
        if (property_exists($DeviceState, 'on')) {
            $this->SetValue('HUE_State', $DeviceState->on);
        }
        if (property_exists($DeviceState, 'bri')) {
            $this->SetValue('HUE_Brightness', $DeviceState->bri);
        }
        if (property_exists($DeviceState, 'presence')) {
            $this->SetValue('HUE_Presence', $DeviceState->presence);
        }
        if (property_exists($DeviceConfig, 'battery')) {
            $this->SetValue('HUE_Battery', $DeviceConfig->battery);
        }
        if (property_exists($DeviceState, 'lightlevel')) {
            $this->SetValue('HUE_Lightlevel', $DeviceState->lightlevel);
        }
        if (property_exists($DeviceState, 'dark')) {
            $this->SetValue('HUE_Dark', $DeviceState->dark);
        }
        if (property_exists($DeviceState, 'daylight')) {
            $this->SetValue('HUE_Daylight', $DeviceState->daylight);
        }
        if (property_exists($DeviceState, 'temperature')) {
            $this->SetValue('HUE_Temperature', $DeviceState->temperature);
        }
        if (property_exists($DeviceState, 'buttonevent')) {
            $this->SetValue('HUE_Buttonevent', $DeviceState->buttonevent);
        }
    }

    public function SwitchMode(bool $Value)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        $params = array('on' => $Value);
        return $this->sendData($command, $params);
    }

    public function DimSet(int $Value)
    {
        if ($this->ReadPropertyString('DeviceType') == 'groups') {
            $command = 'action';
        } else {
            $command = 'state';
        }

        $params = array('bri' => $Value, 'on' => true);
        return $this->sendData($command, $params);
    }

    public function ColorSet($Value)
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

        $params = array('bri' => $ConvertedXY['bri'], 'xy' => $xy, 'on' => true);
        return $this->sendData($command, $params);
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
                if (array_key_exists('success', $result[1])) {
                    $this->SetValue($Ident, $Value);
                }
                if (array_key_exists('success', $result[2])) {
                    $this->SetValue('HUE_Brightness', $result[2]['success']['/lights/' . $this->ReadPropertyString('HUEDeviceID') . '/state/bri']);
                }
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'Invalid Ident', 0);
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

        $this->SendDebug(__FUNCTION__, $Data, 0);
        $Data = json_decode($this->SendDataToParent($Data), true);
        return $Data;
    }
}
