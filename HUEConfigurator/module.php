<?php

declare(strict_types=1);

class HUEConfigurator extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{6EFF1F3C-DF5F-43F7-DF44-F87EFF149566}');
        $this->RegisterPropertyInteger('TargetCategory', 0);

        $this->RegisterAttributeInteger('ProgressStatus', -1);
        $this->RegisterTimer('ProgressNewDevices', 0, 'PHUE_ProgressUpdateNewDevicesList(' . $this->InstanceID . ');');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
    }

    public function GetConfigurationForm()
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $Lights = $this->getHUELights();
        $Groups = $this->getHUEGroups();
        $Sensors = $this->getHUESensors();

        if (array_key_exists('error', $Lights)) {
            IPS_LogMessage('HUE Configuration Error', $Lights['error']['type'] . ': ' . $Lights['error']['description']);
            return $Form;
        }
        if (array_key_exists('error', $Groups)) {
            IPS_LogMessage('HUE Configuration Error', $Groups['error']['type'] . ': ' . $Groups['error']['description']);
            return $Form;
        }
        if (array_key_exists('error', $Sensors)) {
            IPS_LogMessage('HUE Configuration Error', $Sensors['error']['type'] . ': ' . $Sensors['error']['description']);
            return $Form;
        }

        $this->SendDebug(__FUNCTION__ . ' Lights', json_encode($Lights), 0);
        $this->SendDebug(__FUNCTION__ . ' Groups', json_encode($Groups), 0);
        $this->SendDebug(__FUNCTION__ . ' Sensors', json_encode($Sensors), 0);

        $Values = [];
        $ValuesAllDevices = [];

        $location = $this->getPathOfCategory($this->ReadPropertyInteger('TargetCategory'));
        //Lights
        if (count($Lights) > 0) {
            $AddValueLights = [
                'id'                    => 1,
                'ID'                    => '',
                'name'                  => 'Lights',
                'DisplayName'           => $this->translate('Lights'),
                'Type'                  => '',
                'ModelID'               => '',
                'Manufacturername'      => '',
                'Productname'           => ''
            ];

            $AddValueAllDevicesLights = [
                'id'                    => 99999,
                'DeviceID'              => '',
                'DeviceName'            => $this->translate('Lights'),
                'DeviceType'            => ''
            ];

            $Values[] = $AddValueLights;
            $ValuesAllDevices[] = $AddValueAllDevicesLights;

            foreach ($Lights as $key => $light) {
                $instanceID = $this->getHUEDeviceInstances($key, 'lights');

                $AddValueLights = [
                    'parent'                => 1,
                    'ID'                    => $key,
                    'DisplayName'           => $light['name'],
                    'name'                  => $light['name'],
                    'Type'                  => $light['type'],
                    'ModelID'               => $light['modelid'],
                    'Manufacturername'      => $light['manufacturername'],
                    'Productname'           => ((array_key_exists('productname', $light)) ? $light['productname'] : '-'),
                    'instanceID'            => $instanceID
                ];

                $AddValueAllDevicesLights = [
                    'parent'                => 99999,
                    'id'                    => $key,
                    'DeviceID'              => $key,
                    'DeviceName'            => $light['name'],
                    'DeviceType'            => 'lights'
                ];

                $AddValueLights['create'] = [
                    'moduleID'      => '{83354C26-2732-427C-A781-B3F5CDF758B1}',
                    'configuration' => [
                        'HUEDeviceID'    => $key,
                        'DeviceType'     => 'lights'
                    ],
                    'location' => $location
                ];

                $Values[] = $AddValueLights;
                $ValuesAllDevices[] = $AddValueAllDevicesLights;
            }
        }

        //Sensors
        if (count($Sensors) > 0) {
            $AddValueSensors = [
                'id'                    => 2,
                'ID'                    => '',
                'name'                  => 'Sensors',
                'DisplayName'           => $this->translate('Sensors'),
                'Type'                  => '',
                'ModelID'               => '',
                'Manufacturername'      => '',
                'Productname'           => ''
            ];

            $AddValueAllDevicesSensors = [
                'id'                    => 99998,
                'DeviceID'              => '',
                'DeviceName'            => $this->translate('Sensors'),
                'DeviceType'            => ''
            ];

            $Values[] = $AddValueSensors;
            $ValuesAllDevices[] = $AddValueAllDevicesSensors;

            foreach ($Sensors as $key => $sensor) {
                $instanceID = $this->getHUEDeviceInstances($key, 'sensors');

                $AddValueSensors = [
                    'parent'                => 2,
                    'ID'                    => $key,
                    'DisplayName'           => $sensor['name'],
                    'name'                  => $sensor['name'],
                    'Type'                  => $sensor['type'],
                    'ModelID'               => $sensor['modelid'],
                    'Manufacturername'      => $sensor['manufacturername'],
                    'Productname'           => '-',
                    'instanceID'            => $instanceID
                ];

                $AddValueAllDevicesSensors = [
                    'parent'                => 99998,
                    'id'                    => $key,
                    'DeviceID'              => $key,
                    'DeviceName'            => $sensor['name'],
                    'DeviceType'            => 'sensors'
                ];

                $AddValueSensors['create'] = [
                    'moduleID'      => '{83354C26-2732-427C-A781-B3F5CDF758B1}',
                    'configuration' => [
                        'HUEDeviceID'    => $key,
                        'DeviceType'     => 'sensors',
                        'SensorType'     => $sensor['type']
                    ],
                    'location' => $location
                ];

                $Values[] = $AddValueSensors;
                $ValuesAllDevices[] = $AddValueAllDevicesSensors;
            }
        }

        //DeviceManagement AllDevices
        $Form['actions'][1]['items'][6]['values'] = $ValuesAllDevices;

        //Groups
        if (count($Groups) > 0) {
            $AddValueGroups = [
                'id'                    => 3,
                'ID'                    => '',
                'name'                  => 'Groups',
                'DisplayName'           => $this->translate('Groups'),
                'Type'                  => '',
                'ModelID'               => '',
                'Manufacturername'      => '',
                'Productname'           => ''
            ];
            $Values[] = $AddValueGroups;
            foreach ($Groups as $key => $group) {
                $instanceID = $this->getHUEDeviceInstances($key, 'groups');
                if ($group['type'] != 'Entertainment') {
                    $AddValueGroups = [
                        'parent'                => 3,
                        'ID'                    => $key,
                        'DisplayName'           => $group['name'],
                        'name'                  => $group['name'],
                        'Type'                  => $group['type'],
                        'DeviceType'            => 'Group',
                        'ModelID'               => '-',
                        'Manufacturername'      => '-',
                        'Productname'           => '-',
                        'instanceID'            => $instanceID
                    ];

                    $AddValueGroups['create'] = [
                        'moduleID'      => '{83354C26-2732-427C-A781-B3F5CDF758B1}',
                        'configuration' => [
                            'HUEDeviceID'    => $key,
                            'DeviceType'     => 'groups'
                        ],
                        'location' => $location
                    ];
                    $Values[] = $AddValueGroups;
                }
            }
        }
        $Form['actions'][0]['values'] = $Values;
        return json_encode($Form);
    }

    private function getHUEDeviceInstances($HueDeviceID, $DeviceType)
    {
        $InstanceIDs = IPS_GetInstanceListByModuleID('{83354C26-2732-427C-A781-B3F5CDF758B1}'); //HUEDevice
        foreach ($InstanceIDs as $id) {
            if (IPS_GetProperty($id, 'HUEDeviceID') == $HueDeviceID && IPS_GetProperty($id, 'DeviceType') == $DeviceType) {
                return $id;
            }
        }
        return 0;
    }

    private function getHUELights()
    {
        $Data = [];
        $Buffer = [];

        $Data['DataID'] = '{03995C27-F41C-4E0C-85C9-099084294C3B}';
        $Buffer['Command'] = 'getAllLights';
        $Buffer['Params'] = '';
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        return $result;
    }

    private function getHUEGroups()
    {
        $Data = [];
        $Buffer = [];

        $Data['DataID'] = '{03995C27-F41C-4E0C-85C9-099084294C3B}';
        $Buffer['Command'] = 'getAllGroups';
        $Buffer['Params'] = '';
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        return $result;
    }

    private function getHUESensors()
    {
        $Data = [];
        $Buffer = [];

        $Data['DataID'] = '{03995C27-F41C-4E0C-85C9-099084294C3B}';
        $Buffer['Command'] = 'getAllSensors';
        $Buffer['Params'] = '';
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        return $result;
    }

    public function reloadAllDevices()
    {
        $Lights = $this->getHUELights();
        $Sensors = $this->getHUESensors();

        //Lights
        if (count($Lights) > 0) {
            $AddValueAllDevicesLights = [
                'id'                    => 99999,
                'DeviceID'              => '',
                'DeviceName'            => $this->translate('Lights'),
                'DeviceType'            => '',
                'expanded'              => true
            ];
        }
        $ValuesAllDevices[] = $AddValueAllDevicesLights;

        foreach ($Lights as $key => $light) {
            $AddValueAllDevicesLights = [
                'parent'                => 99999,
                'id'                    => $key,
                'DeviceID'              => $key,
                'DeviceName'            => $light['name'],
                'DeviceType'            => 'lights'
            ];
            $ValuesAllDevices[] = $AddValueAllDevicesLights;
        }
        //Sensors
        if (count($Sensors) > 0) {
            $AddValueAllDevicesSensors = [
                'id'                    => 99998,
                'DeviceID'              => '',
                'DeviceName'            => $this->translate('Sensors'),
                'DeviceType'            => '',
                'expanded'              => true
            ];
            $ValuesAllDevices[] = $AddValueAllDevicesSensors;
            foreach ($Sensors as $key => $sensor) {
                $AddValueAllDevicesSensors = [
                    'parent'                => 99998,
                    'id'                    => $key,
                    'DeviceID'              => $key,
                    'DeviceName'            => $sensor['name'],
                    'DeviceType'            => 'sensors'
                ];
                $ValuesAllDevices[] = $AddValueAllDevicesSensors;
            }
        }
        $this->UpdateFormField('AllDevices', 'values', json_encode($ValuesAllDevices));
    }

    private function getPathOfCategory(int $categoryId): array
    {
        if ($categoryId === 0) {
            return [];
        }

        $path[] = IPS_GetName($categoryId);
        $parentId = IPS_GetObject($categoryId)['ParentID'];

        while ($parentId > 0) {
            $path[] = IPS_GetName($parentId);
            $parentId = IPS_GetObject($parentId)['ParentID'];
        }

        return array_reverse($path);
    }

    //Functions for Device Management / Pairing (New Devices)

    public function renameDevice(string $NewName, $DeviceID, $DeviceType)
    {
        $Data = [];
        $Buffer = [];
        $Data['DataID'] = '{03995C27-F41C-4E0C-85C9-099084294C3B}';
        $Buffer['Command'] = 'renameDevice';
        $Buffer['DeviceType'] = $DeviceType;
        $Buffer['DeviceID'] = $DeviceID;
        $Buffer['Params'] = ['name' => $NewName];
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        $this->parseError($result);
        $this->reloadAllDevices();
    }

    public function deleteDevice($DeviceID, $DeviceType)
    {
        $Data = [];
        $Buffer = [];
        $Data['DataID'] = '{03995C27-F41C-4E0C-85C9-099084294C3B}';
        $Buffer['Command'] = 'deleteDevice';
        $Buffer['DeviceType'] = $DeviceType;
        $Buffer['DeviceID'] = $DeviceID;
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        $this->parseError($result);
        $this->reloadAllDevices();
    }

    public function scanNewDevices()
    {
        $Data = [];
        $Buffer = [];

        $Data['DataID'] = '{03995C27-F41C-4E0C-85C9-099084294C3B}';
        $Buffer['Command'] = 'scanNewDevices';
        $Buffer['Params'] = '';
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        $this->UpdateFormField('LastScan', 'caption', $result[0]['success']['/lights']);
        //Progress Timer für getNewDevice
        $this->SetTimerInterval('ProgressNewDevices', 1000);
        return $result;
    }

    public function getNewDevices($DeviceType)
    {
        $Data = [];
        $Buffer = [];

        $Data['DataID'] = '{03995C27-F41C-4E0C-85C9-099084294C3B}';

        switch ($DeviceType) {
            case 'Lights':
                $Buffer['Command'] = 'getNewLights';
                break;
            case 'Sensors':
                $Buffer['Command'] = 'getNewSensors';
                break;
            default:
                return [];
            }

        $Buffer['Params'] = '';
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        return $result;
    }

    public function getGroupAttributes($id)
    {
        $Data = [];
        $Buffer = [];
        $Data['DataID'] = '{03995C27-F41C-4E0C-85C9-099084294C3B}';
        $Buffer['Command'] = 'getGroupAttributes';
        $Buffer['Params'] = ['GroupID' => $id];
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        return $result;
    }

    public function ProgressUpdateNewDevicesList()
    {
        $ValuesLights = [];
        $ValuesSensors = [];
        $NewLights = $this->getNewDevices('Lights');
        $NewSensors = $this->getNewDevices('Sensors');

        $this->WriteAttributeInteger('ProgressStatus', $this->ReadAttributeInteger('ProgressStatus') + 1);
        $this->UpdateFormField('ProgressNewDevices', 'current', $this->ReadAttributeInteger('ProgressStatus'));

        $this->UpdateFormField('LastScan', 'caption', $NewLights['lastscan']);
        //For Debug
        //sleep(3);
        //$NewDevices = json_decode('{"7": {"name": "Hue Lamp 7"},"8": {"name": "Hue Lamp 8"},"lastscan": "2012-10-29T12:00:00"}',true);
        foreach ($NewLights as $key => $Light) {
            if ($key != 'lastscan') {
                $ValueNewLight = [
                    'DeviceID'   => $key,
                    'DeviceName' => $Light['name']
                ];
                $ValuesLights[] = $ValueNewLight;
            }
        }
        $this->UpdateFormField('NewLights', 'values', json_encode($ValuesLights));

        foreach ($NewSensors as $key => $Sensor) {
            if ($key != 'lastscan') {
                $ValueNewSensor = [
                    'DeviceID'   => $key,
                    'DeviceName' => $Sensor['name']
                ];
                $ValuesSensors[] = $ValueNewSensor;
            }
        }
        $this->UpdateFormField('NewSensors', 'values', json_encode($ValuesSensors));

        if ($NewLights['lastscan'] != 'active' && $NewSensors['lastscan'] != 'active') {
            $this->SetTimerInterval('ProgressNewDevices', 0);
            $this->WriteAttributeInteger('ProgressStatus', 0);
        }
    }

    //Group Function

    public function LoadGroupConfigurationForm()
    {
        $this->UpdateGroupsForConfiguration();
        $this->UpdateLightsForNewGroup();
    }

    private function UpdateGroupsForConfiguration()
    {
        $Option = [
            'caption'   => 'All',
            'value'     => 0,
        ];

        $Options[] = $Option;

        $Groups = $this->getHUEGroups();
        foreach ($Groups as $key => $group) {
            if ($group['type'] != 'Entertainment') {
                $Option = [
                    'caption'   => $group['name'],
                    'value'     => $key,
                ];
                $Options[] = $Option;
            }
            $this->UpdateFormField('Groups', 'options', json_encode($Options));
        }
    }

    public function UpdateAllLightsInGroupsForConfiguration(int $id)
    {
        $Group = $this->getGroupAttributes($id);
        foreach ($Group['lights'] as $key => $light) {
            $Value = [
                'DeviceID'   => $light,
                'DeviceName' => '',
            ];
            $Values[] = $Value;
        }

        if (empty($Group['lights'])) {
            $Values = [];
        }

        $this->UpdateFormField('AllLightsInGroup', 'values', json_encode($Values));
    }

    private function UpdateLightsForNewGroup()
    {
        $Lights = $this->getHUELights();
        foreach ($Lights as $key => $light) {
            $Value = [
                'DeviceID'   => $key,
                'DeviceName' => $light['name']
            ];
            $Values[] = $Value;
        }
        $this->UpdateFormField('AllLights', 'values', json_encode($Values));
    }

    public function createGroup(string $GroupName, string $GroupType, string $class = 'Other', int $Light = 0)
    {
        $Buffer = [];
        $Data = [];

        if ($GroupType == 'Room') {
            $Buffer['Params'] = ['name' => $GroupName, 'type' => $GroupType, 'class' => $class];
        } else {
            if ($Light == 0) {
                $this->UpdateFormField('PopupLightGroupFailed', 'visible', 'true');
                return;
            }
            $Buffer['Params'] = ['name' => $GroupName, 'type' => $GroupType, 'lights' => [strval($Light)]];
        }
        $Data['DataID'] = '{03995C27-F41C-4E0C-85C9-099084294C3B}';
        $Buffer['Command'] = 'createGroup';
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        if ($this->parseError($result)) {
            $this->LoadGroupConfigurationForm();
        }
    }

    public function addLightToGroup($DeviceID, $GroupID)
    {
        $Group = $this->getGroupAttributes($GroupID);

        if (array_key_exists('lights', $Group)) {
            array_push($Group['lights'], strval($DeviceID));
        } else {
            $Group['lights'][0] = strval($DeviceID);
        }
        $params = ['name' => $Group['name'], 'lights' => $Group['lights']];

        $this->setGroupAttributes($GroupID, $params);
        $this->UpdateAllLightsInGroupsForConfiguration($GroupID);
    }

    public function deleteLightFromGroup($DeviceID, $GroupID)
    {
        $Group = $this->getGroupAttributes($GroupID);

        if (array_key_exists('lights', $Group)) {
            $key = array_search($DeviceID, $Group['lights']);
            unset($Group['lights'][$key]);
            $Group['lights'] = array_values($Group['lights']);
        } else {
            return;
        }
        $params = ['name' => $Group['name'], 'lights' => $Group['lights']];

        $this->setGroupAttributes($GroupID, $params);
        $this->UpdateAllLightsInGroupsForConfiguration($GroupID);
    }

    private function setGroupAttributes($GroupID, $params)
    {
        $Data = [];
        $Buffer = [];
        $Data['DataID'] = '{03995C27-F41C-4E0C-85C9-099084294C3B}';
        $Buffer['Command'] = 'setGroupAttributes';
        $Buffer['GroupID'] = $GroupID;
        $Buffer['Params'] = $params;
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        $this->parseError($result);
    }

    public function deleteGroup($GroupID)
    {
        $Data = [];
        $Buffer = [];
        $Data['DataID'] = '{03995C27-F41C-4E0C-85C9-099084294C3B}';
        $Buffer['Command'] = 'deleteGroup';
        $Buffer['GroupID'] = $GroupID;
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $result = json_decode($this->SendDataToParent($Data), true);
        if (!$result) {
            return [];
        }
        if ($this->parseError($result)) {
            $this->LoadGroupConfigurationForm();
            $this->UpdateFormField('AllLightsInGroup', 'values', json_encode([]));
        }
    }

    //End Functions for Group Gonfigurator

    private function parseError($result)
    {
        if (array_key_exists('error', $result[0])) {
            IPS_LogMessage('Philips HUE Error', $result[0]['error']['type'] . ': ' . $result[0]['error']['address'] . ' - ' . $result[0]['error']['description']);
            $this->UpdateFormField('PopupFailed', 'visible', true);
            return false;
        } elseif (array_key_exists('success', $result[0])) {
            $this->UpdateFormField('PopupSuccess', 'visible', true);
            return true;
        } else {
            IPS_LogMessage('Philips HUE unknown Error', print_r($result, true));
            $this->UpdateFormField('PopupFailed', 'visible', true);
            return false;
        }
    }
}
