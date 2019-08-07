<?php

declare(strict_types=1);

class HUEConfigurator extends IPSModule
{
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{6EFF1F3C-DF5F-43F7-DF44-F87EFF149566}');
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

        $this->SendDebug(__FUNCTION__, json_encode($Lights), 0);

        $Values = [];

        //Lights
        if (count($Lights) > 0) {
            $AddValueLights = [
                'id'                    => 1,
                'ID'                    => '',
                'name'                  => 'Lights',
                'DisplayName'           => 'Lights',
                'Type'                  => '',
                'ModelID'               => '',
                'Manufacturername'      => '',
                'Productname'           => ''
            ];
            $Values[] = $AddValueLights;
            foreach ($Lights as $key => $light) {
                $instanceID = $this->getHUEDeviceInstances($key, 'lights');

                $AddValueLights = [
                    'parent'                => 1,
                    'ID'                    => $key,
                    'DisplayName'           => $light['name'],
                    'Type'                  => $light['type'],
                    'ModelID'               => $light['modelid'],
                    'Manufacturername'      => $light['manufacturername'],
                    'Productname'           => $light['productname'],
                    'instanceID'            => $instanceID
                ];

                $AddValueLights['create'] = [
                    $light['name'] => [
                        'moduleID'      => '{83354C26-2732-427C-A781-B3F5CDF758B1}',
                        'configuration' => [
                            'HUEDeviceID'    => $key,
                            'DeviceType'     => 'lights'
                        ]
                    ]
                ];

                $Values[] = $AddValueLights;
            }
        }

        //Sensors
        if (count($Sensors) > 0) {
            $AddValueSensors = [
                'id'                    => 2,
                'ID'                    => '',
                'name'                  => 'Sensors',
                'DisplayName'           => 'Sensors',
                'Type'                  => '',
                'ModelID'               => '',
                'Manufacturername'      => '',
                'Productname'           => ''
            ];

            $Values[] = $AddValueSensors;
            foreach ($Sensors as $key => $sensor) {
                $instanceID = $this->getHUEDeviceInstances($key, 'sensors');

                $AddValueSensors = [
                    'parent'                => 2,
                    'ID'                    => $key,
                    'DisplayName'           => $sensor['name'],
                    'Type'                  => $sensor['type'],
                    'ModelID'               => $sensor['modelid'],
                    'Manufacturername'      => $sensor['manufacturername'],
                    'Productname'           => '-',
                    'instanceID'            => $instanceID
                ];

                $AddValueSensors['create'] = [
                    $sensor['name'] => [
                        'moduleID'      => '{83354C26-2732-427C-A781-B3F5CDF758B1}',
                        'configuration' => [
                            'HUEDeviceID'    => $key,
                            'DeviceType'     => 'sensors',
                            'SensorType'     => $sensor['type']
                        ]
                    ]
                ];

                $Values[] = $AddValueSensors;
            }
        }

        //Groups

        if (count($Groups) > 0) {
            $AddValueGroups = [
                'id'                    => 3,
                'ID'                    => '',
                'name'                  => 'Groups',
                'DisplayName'           => 'Groups',
                'Type'                  => '',
                'ModelID'               => '',
                'Manufacturername'      => '',
                'Productname'           => ''
                ];
            $Values[] = $AddValueGroups;
            foreach ($Groups as $key => $group) {
                $instanceID = $this->getHUEDeviceInstances($key, 'groups');

                $AddValueGroups = [
                    'parent'                => 3,
                    'ID'                    => $key,
                    'DisplayName'           => $group['name'],
                    'Type'                  => $group['type'],
                    'ModelID'               => '-',
                    'Manufacturername'      => '-',
                    'Productname'           => '-',
                    'instanceID'            => $instanceID
                ];

                $AddValueGroups['create'] = [
                    $group['name'] => [
                        'moduleID'      => '{83354C26-2732-427C-A781-B3F5CDF758B1}',
                        'configuration' => [
                            'HUEDeviceID'    => $key,
                            'DeviceType'     => 'groups'
                        ]
                    ]
                ];

                $Values[] = $AddValueGroups;
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
}
