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
        $Bridges = $this->mDNSDiscoverBridges();

        $Values = [];

        foreach ($Bridges as $Bridge) {
            $instanceID = $this->getHUEBridgeInstances($Bridge['IPv4']);

            $AddValue = [
                'IPAddress'             => $Bridge['IPv4'],
                'name'                  => $Bridge['deviceName'],
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
                        'Host' => $Bridge['IPv4']
                    ]
                ]

            ];

            $Values[] = $AddValue;
        }
        $Form['actions'][0]['values'] = $Values;
        return json_encode($Form);
    }

    public function mDNSDiscoverBridges()
    {
        $mDNSInstanceIDs = IPS_GetInstanceListByModuleID('{780B2D48-916C-4D59-AD35-5A429B2355A5}');
        $resultServiceTypes = ZC_QueryServiceType($mDNSInstanceIDs[0], '_hue._tcp', '');
        $this->SendDebug('mDNS resultServiceTypes', print_r($resultServiceTypes, true), 0);
        $bridges = [];
        foreach ($resultServiceTypes as $key => $device) {
            $hue = [];
            $deviceInfo = ZC_QueryService($mDNSInstanceIDs[0], $device['Name'], '_hue._tcp', 'local.');
            $this->SendDebug('mDNS QueryService', $device['Name'] . ' ' . $device['Type'] . ' ' . $device['Domain'] . '.', 0);
            $this->SendDebug('mDNS QueryService Result', print_r($deviceInfo, true), 0);
            if (!empty($deviceInfo)) {
                $hue['Hostname'] = $deviceInfo[0]['Host'];
                $hue['IPv4'] = $deviceInfo[0]['IPv6'][0]; //IPv4 und IPv6 sind vertauscht
                $hueData = $this->readBridgeDataFromXML($hue['IPv4']);
                $hue['deviceName'] = (string) $hueData->device->friendlyName;
                $hue['modelName'] = (string) $hueData->device->modelName;
                $hue['modelNumber'] = (string) $hueData->device->modelNumber;
                $hue['serialNumber'] = (string) $hueData->device->serialNumber;
                array_push($bridges, $hue);
            }
        }
        return $bridges;
    }

    private function DiscoverBridgesOld() // wird später entfernt
    {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$socket) {
            return [];
        }
        socket_bind($socket, '0.0.0.0', 0);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 0, 'usec' => 100000]);
        socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
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
            $this->SendDebug('url', $Url, 0);
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

    private function readBridgeDataFromXML($ip)
    {
        $XMLData = file_get_contents('http://' . $ip . ':80/description.xml');
        if ($XMLData === false) {
            return;
        }
        $Xml = new SimpleXMLElement($XMLData);

        $modelName = (string) $Xml->device->modelName;
        if (strpos($modelName, 'Philips hue bridge') === false) {
            return;
        }
        return $Xml;
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
