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

        $this->SendDebug(__FUNCTION__, json_encode($Lights), 0);

        $Values = array();

        //Lights
        if (count($Lights) > 0) {
            $AddValue = array(
                'id'                    => 1,
                'ID'                    => '',
                'name'                  => 'Lights',
                'Name'                  => 'Lights',
                'Type'                  => '',
                'ModelID'               => '',
                'Manufacturername'      => '',
                'Productname'           => ''
            );
            $Values[] = $AddValue;
            foreach ($Lights as $key => $light) {
                $instanceID = $this->getHUEDeviceInstances($key, 'lights');

                $AddValue = array(
                    'parent'                => 1,
                    'ID'                    => $key,
                    'Name'                  => $light['name'],
                    'Type'                  => $light['type'],
                    'ModelID'               => $light['modelid'],
                    'Manufacturername'      => $light['manufacturername'],
                    'Productname'           => $light['productname'],
                    'instanceID'            => $instanceID
                );

                $AddValue['create'] = array(
                    'HUEDevice' => array(
                        'moduleID'      => '{83354C26-2732-427C-A781-B3F5CDF758B1}',
                        'configuration' => array(
                            'HUEDeviceID'    => $key,
                            'DeviceType'     => 'lights'
                        )
                    )
                );

                $Values[] = $AddValue;
            }
            //$Form['actions'][0]['values'] = $Values;
        }

        //Groups

        if (count($Groups) > 0) {
            $AddValue = array(
                'id'                    => 3,
                'ID'                    => '',
                'name'                  => 'Groups',
                'Name'                  => 'Groups',
                'Type'                  => '',
                'ModelID'               => '',
                'Manufacturername'      => '',
                'Productname'           => ''
                );
            $Values[] = $AddValue;
            foreach ($Groups as $key => $group) {
                $instanceID = $this->getHUEDeviceInstances($key, 'groups');

                $AddValue = array(
                    'parent'                => 3,
                    'ID'                    => $key,
                    'Name'                  => $group['name'],
                    'Type'                  => $group['type'],
                    'ModelID'               => '-',
                    'Manufacturername'      => '-',
                    'Productname'           => '-',
                    'instanceID'            => $instanceID
                );

                $AddValue['create'] = array(
                    'HUEDevice' => array(
                        'moduleID'      => '{83354C26-2732-427C-A781-B3F5CDF758B1}',
                        'configuration' => array(
                            'HUEDeviceID'    => $key,
                            'DeviceType'     => 'groups'
                        )
                    )
                );

                $Values[] = $AddValue;
            }
        }
        $Form['actions'][0]['values'] = $Values;
        return json_encode($Form);
    }

    private function getHUEDeviceInstances($HueDeviceID, $DeviceType)
    {
        $InstanceIDs = IPS_GetInstanceListByModuleID('{83354C26-2732-427C-A781-B3F5CDF758B1}');
        foreach ($InstanceIDs as $id) {
            if (IPS_GetProperty($id, 'HUEDeviceID') == $HueDeviceID && IPS_GetProperty($id, 'DeviceType') == $DeviceType) {
                return $id;
            }
        }
        return 0;
    }

    private function getHUELights()
    {
        $Data['DataID'] = '{03995C27-F41C-4E0C-85C9-099084294C3B}';
        $Buffer['Command'] = 'getAllLights';
        $Buffer['Params'] = '';
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $Data = json_decode($this->SendDataToParent($Data), true);
        if (!$Data) {
            return array();
        }
        return $Data;
    }

    private function getHUEGroups()
    {
        $Data['DataID'] = '{03995C27-F41C-4E0C-85C9-099084294C3B}';
        $Buffer['Command'] = 'getAllGroups';
        $Buffer['Params'] = '';
        $Data['Buffer'] = $Buffer;
        $Data = json_encode($Data);
        $Data = json_decode($this->SendDataToParent($Data), true);
        if (!$Data) {
            return array();
        }
        return $Data;
    }
}
