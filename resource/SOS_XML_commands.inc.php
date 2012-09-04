<?PHP
$sos_command['getCapabilities'] = <<<EOX
<GetCapabilities xmlns="http://www.opengis.net/sos/1.0"
  xmlns:ows="http://www.opengis.net/ows/1.1"
  xmlns:ogc="http://www.opengis.net/ogc"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://www.opengis.net/sos/1.0
  http://schemas.opengis.net/sos/1.0.0/sosGetCapabilities.xsd"
  service="SOS">
  
  <ows:AcceptVersions>
    <ows:Version>1.0.0</ows:Version>
  </ows:AcceptVersions>
  
  <ows:Sections>  
    <ows:Section>OperationsMetadata</ows:Section>  
    <ows:Section>Contents</ows:Section>
  </ows:Sections>

</GetCapabilities>
EOX;

$sos_command['getObservation'] = <<< EOX
<GetObservation xmlns="http://www.opengis.net/sos/1.0"
  xmlns:ows="http://www.opengis.net/ows/1.1"
  xmlns:gml="http://www.opengis.net/gml"
  xmlns:ogc="http://www.opengis.net/ogc"
  xmlns:om="http://www.opengis.net/om/1.0"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://www.opengis.net/sos/1.0
  http://schemas.opengis.net/sos/1.0.0/sosGetObservation.xsd"
  service="SOS" version="1.0.0" srsName="urn:ogc:def:crs:EPSG::4326">
  
  <offering>%offering%</offering>
  
  <eventTime>
    <ogc:TM_During>
      <ogc:PropertyName>om:samplingTime</ogc:PropertyName>
      <gml:TimePeriod>
        <gml:beginPosition>%beginPosition%</gml:beginPosition>
        <gml:endPosition>%endPosition%</gml:endPosition>
      </gml:TimePeriod>
    </ogc:TM_During>
  </eventTime>
  
  <observedProperty>%property%</observedProperty>
  <responseFormat>text/xml;subtype=&quot;om/1.0.0&quot;</responseFormat>

</GetObservation>
EOX;
?>
