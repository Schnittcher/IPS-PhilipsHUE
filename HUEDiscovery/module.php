<?php

declare(strict_types=1);
require_once __DIR__ . '/../libs/DebugHelper.php';

class HUEDiscovery extends IPSModule
{
    use DebugHelper;

    public function Create()
    {
        //Never delete this line!
        parent::Create();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
    }

    public function GetConfigurationForm()
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        $Bridges = $this->DiscoverBridges();

        $Values = [];

            foreach ($Bridges as $IPAddress => $Bridge) {
                $instanceID = $this->getHUEBridgeInstances($IPAddress);

                $AddValue = array(
                    'IPAddress'             => $IPAddress,
                    'name'                  => $Bridge['devicename'],
                    'ModelName'             => $Bridge['modelName'],
                    'ModelNumber'           => $Bridge['modelNumber'],
                    'SerialNumber'          => $Bridge['serialNumber'],
                    'instanceID'            => $instanceID
                );

                $AddValue['create'] = array(
                    array(
                        'moduleID'      => '{EE92367A-BB8B-494F-A4D2-FAD77290CCF4}',
                        'configuration' => new stdClass()
                    ),
                    array(
                        'moduleID'      => '{6EFF1F3C-DF5F-43F7-DF44-F87EFF149566}',
                        'configuration' => array(
                            'Host' => $IPAddress
                        )
                    )

                );

                $Values[] = $AddValue;
            }
            $Form['actions'][0]['values'] = $Values;
        return json_encode($Form);
    }

    private function getHUEBridgeInstances($IPAddress)
    {
        $InstanceIDs = IPS_GetInstanceListByModuleID('{6EFF1F3C-DF5F-43F7-DF44-F87EFF149566}');
        foreach ($InstanceIDs as $id) {
            if (IPS_GetProperty($id, 'Host') == $IPAddress) {
                return $id;
            }
        }
        return 0;
    }

    public function DiscoverBridges()
    {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$socket) {
            return array();
        }
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 2, 'usec' => 100000));
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($socket, '0.0.0.0', 0);
        $message = array(
            'M-SEARCH * HTTP/1.1',
            'ST: ssdp:all',
            'MX: 3',
            'MAN: "ssdp:discover"',
            'HOST: 239.255.255.250:1900'
        );
        $SendData = implode("\r\n", $message) . "\r\n\r\n";
        $this->SendDebug('Search', $SendData, 0);
        if (@socket_sendto($socket, $SendData, strlen($SendData), 0, '239.255.255.250', 1900) === false) {
            return array();
        }
        usleep(100000);
        $i = 50;
        $IPAddress = '';
        $Port = 0;
        $BridgeData = array();
        while ($i) {
            $ret = @socket_recvfrom($socket, $buf, 2048, 0, $IPAddress, $Port);
            if ($ret === false) {
                break;
            }
            if ($ret === 0) {
                $i--;
                continue;
            }
            $Data = $this->parseHeader($buf);
            if (!array_key_exists('HUE-BRIDGEID', $Data)) {
                continue;
            }
            $BridgeData[$IPAddress] = $Data['LOCATION'];
        }
        socket_close($socket);

        $Bridge = array();
        foreach ($BridgeData as $IPAddress => $Url) {
            $XMLData = @Sys_GetURLContent($Url);
            $this->SendDebug('XML', $XMLData, 0);
            if ($XMLData === false) {
                continue;
            }

            $Xml = new SimpleXMLElement($XMLData);

            $modelName = (string) $Xml->device->modelName;
            if (strpos($modelName, 'Philips hue bridge') === false) {
                continue;
            }
            $Bridge[$IPAddress] = array(
                'devicename'   => (string) $Xml->device->friendlyName,
                'modelName'    => (string) $Xml->device->modelName,
                'modelNumber'  => (string) $Xml->device->modelNumber,
                'serialNumber' => (string) $Xml->device->serialNumber
            );
        }
        return $Bridge;
    }

    private function parseHeader(string $Data): array
    {
        $Lines = explode("\r\n", $Data);
        array_shift($Lines);
        array_pop($Lines);
        $Header = array();
        foreach ($Lines as $Line) {
            $line_array = explode(':', $Line);
            $Header[strtoupper(trim(array_shift($line_array)))] = trim(implode(':', $line_array));
        }
        return $Header;
    }
}
