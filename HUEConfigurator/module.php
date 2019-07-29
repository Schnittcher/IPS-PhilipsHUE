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
        $data = json_decode(file_get_contents(__DIR__ . '/form.json'));
        $Devices = $this->getHUELights();

        IPS_LogMessage('test',print_r($Devices,true));
        $this->SendDebug(__FUNCTION__, json_encode($Devices), 0);
        if (count($Devices) > 0) {
            foreach ($Devices as $key => $device) {
                $instanceID = $this->getHUEDeviceInstances($key);
                $data->actions[0]->values[] = array(
                    'ID'                    => $key,
                    'Name'                  => $device['name'],
                    'Type'                  => $device['type'],
                    'ModelID'               => $device['modelid'],
                    'Manufacturername'      => $device['manufacturername'],
                    'Productname'           => $device['productname'],
                    'instanceID'            => $instanceID,
                    'create'                => array(
                        'HUEDevice'     => array(
                            'moduleID'      => '{83354C26-2732-427C-A781-B3F5CDF758B1}',
                            'configuration' => array(
                                'HUEDeviceID'    => $key
                            )
                        )
                    )
                );
            }
        }
        return json_encode($data);
    }

    private function getHUEDeviceInstances($HueDeviceID)
    {
        $InstanceIDs = IPS_GetInstanceListByModuleID('{83354C26-2732-427C-A781-B3F5CDF758B1}');
        foreach ($InstanceIDs as $id) {
            if (IPS_GetProperty($id, 'HUEDeviceID') == $HueDeviceID) {
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
            return [];
        }
        return $Data;
    }
}
