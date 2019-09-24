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

            $AddValue = [
                'IPAddress'             => $IPAddress,
                'name'                  => $Bridge['devicename'],
                'ModelName'             => $Bridge['modelName'],
                'ModelNumber'           => $Bridge['modelNumber'],
                'SerialNumber'          => $Bridge['serialNumber'],
                'instanceID'            => $instanceID
            ];

            $AddValue['create'] = [
                [
                    'moduleID'      => '{EE92367A-BB8B-494F-A4D2-FAD77290CCF4}',
                    'configuration' => new stdClass()
                ],
                [
                    'moduleID'      => '{6EFF1F3C-DF5F-43F7-DF44-F87EFF149566}',
                    'configuration' => [
                        'Host' => $IPAddress
                    ]
                ]

            ];

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
        $s = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_connect($s, '8.8.8.8', 53); // connecting to a UDP address doesn't send packets
        socket_getsockname($s, $local_ip_address, $port);
        socket_close($s);

        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$socket) {
            return [];
        }
        socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 1, 'usec' => 0]);
        socket_bind($socket, $local_ip_address, $port);
        //socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 3, 'usec' => 100000]);
        //socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
        //socket_bind($socket, '0.0.0.0', 0);
        $message = [
            'M-SEARCH * HTTP/1.1',
            'ST: ssdp:all',
            'MX: 3',
            'MAN: "ssdp:discover"',
            'HOST: 239.255.255.250:1900'
        ];
        $SendData = implode("\r\n", $message) . "\r\n\r\n";
        $this->SendDebug('Search', $SendData, 0);
        if (@socket_sendto($socket, $SendData, strlen($SendData), 0, '239.255.255.250', 1900) === false) {
            return [];
        }
        usleep(100000);
        $IPAddress = '';
        $Port = 0;
        $BridgeData = [];
        do {
            $buf = null;
            $bytes = @socket_recvfrom($socket, $buf, 2048, 0, $IPAddress, $Port);
            if ($bytes === false) {
                break;
            }

            if (!is_null($buf)) {
                $Data = $this->parseHeader($buf);
                if (!array_key_exists('HUE-BRIDGEID', $Data)) {
                    continue;
                }
                $this->SendDebug('IPAddress', $IPAddress, 0);
                $BridgeData[$IPAddress] = $Data['LOCATION'];
            }
        } while (!is_null($buf));
        socket_close($socket);

        $Bridge = [];
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
            $Bridge[$IPAddress] = [
                'devicename'   => (string) $Xml->device->friendlyName,
                'modelName'    => (string) $Xml->device->modelName,
                'modelNumber'  => (string) $Xml->device->modelNumber,
                'serialNumber' => (string) $Xml->device->serialNumber
            ];
        }
        return $Bridge;
    }

    private function parseHeader(string $Data): array
    {
        $Lines = explode("\r\n", $Data);
        array_shift($Lines);
        array_pop($Lines);
        $Header = [];
        foreach ($Lines as $Line) {
            $line_array = explode(':', $Line);
            $Header[strtoupper(trim(array_shift($line_array)))] = trim(implode(':', $line_array));
        }
        return $Header;
    }
}
