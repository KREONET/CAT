<?php
/* 
 *******************************************************************************
 * Copyright 2011-2017 DANTE Ltd. and GÉANT on behalf of the GN3, GN3+, GN4-1 
 * and GN4-2 consortia
 *
 * License: see the web/copyright.php file in the file structure
 *******************************************************************************
 */

/**
 * This file contains class definitions and procedures for 
 * generation of a generic XML description of a 802.1x configurator
 *
 * @author Maja Górecka-Wolniewicz <mgw@umk.pl>
 *
 * @package ModuleWriting
 */
namespace devices\xml;
use Exception;

/**
 * base class extended by every element
 */
class XMLElement {

    private $attributes;
    /**
     * The value of the element.
     * @var string
     */
    private $value;

    protected function getObjectVars($obj) {
        return get_object_vars($obj);
    }

    /**
     *  @var array $AuthMethodElements is used to limit
     *  XML elements present within ServerSideCredentials and
     *  ClientSideCredentials to ones which are relevant
     *  for a given EAP method.
     *  @var array of XLM element names which are allowed
     *  EAP method names are defined in core/EAP.php
     */
    public static $authMethodElements = [
        'server' => [
            \core\common\EAP::TLS => ['CA', 'ServerID'],
            \core\common\EAP::FAST => ['CA', 'ServerID'],
            \core\common\EAP::PEAP => ['CA', 'ServerID'],
            \core\common\EAP::TTLS => ['CA', 'ServerID'],
            \core\common\EAP::PWD => ['ServerID'],
        ],
        'client' => [
            \core\common\EAP::TLS => ['UserName', 'Password', 'ClientCertificate'],
            \core\common\EAP::MSCHAP2 => ['UserName', 'Password', 'OuterIdentity'],
            \core\common\EAP::GTC => ['UserName', 'OneTimeToken'],
            \core\common\EAP::NE_PAP => ['UserName', 'Password', 'OuterIdentity'],
            \core\common\EAP::NE_SILVERBULLET => ['UserName', 'ClientCertificate'],
        ]
    ];

    public function __construct() {
        $this->attributes = [];
        $this->value = '';
    }

    public function setAttributes($attributes) {
        $this->attributes = $attributes;
    }

    public function getAttributes() {
        return $this->attributes;
    }

    /**
     * 
     * @param scalar $value
     * @return void
     */
    public function setValue($value) {
        if (is_scalar($value)) {
            $this->value = strval($value);
        } else {
            throw new Exception("unexpected value type passed" . gettype($value));
        }
    }

    /**
     * 
     * @return string
     */
    public function getValue() {
        return $this->value;
    }

    public function areAttributes() {
        return empty($this->attributes) ? 0 : 1;
    }

    /**
     * adds an attribute with the given value to the set of attributes
     * @param string $attribute
     * @param mixed $value
     */
    public function setAttribute($attribute, $value) {
        if (!isset($this->attributes)) {
            $this->attributes = [];
        }
        $this->attributes[$attribute] = $value;
    }

    /**
     * 
     * @param string $property
     * @param mixed $value
     */
    public function setProperty($property, $value) {
        $this->$property = $value;
    }

    public function getAll() {
        $elems = get_object_vars($this);
        $objvars = [];
        foreach ($elems as $key => $val) {
            if (($key != 'attributes') && ($key != 'value')) {
                $objvars[$key] = $val;
            }
        }
        return $objvars;
    }

}

class EAPIdentityProvider extends XMLElement {

    protected $ValidUntil;
    protected $AuthenticationMethods;
    protected $ProviderInfo;
    protected $VendorSpecific;

}

class AuthenticationMethods extends XMLElement {

    protected $AuthenticationMethod;

}

class AuthenticationMethod extends XMLElement {

    protected $EAPMethod;
    protected $ServerSideCredential;
    protected $ClientSideCredential;
    protected $InnerAuthenticationMethod;

}

class EAPMethod extends XMLElement {

    protected $Type;
    protected $TypeSpecific;
    protected $VendorSpecific;

}

class NonEAPAuthMethod extends XMLElement {

    protected $Type;
    protected $TypeSpecific;
    protected $VendorSpecific;

}

class Type extends XMLElement {
    
}

class TypeSpecific extends XMLElement {
    
}

class VendorSpecific extends XMLElement {
    
}

class ServerSideCredential extends XMLElement {

    protected $CA; // multi
    protected $ServerID; //multi
    protected $EAPType;

    public function getAll() {
        if (isset(XMLElement::$authMethodElements['server'][$this->EAPType]) && XMLElement::$authMethodElements['server'][$this->EAPType]) {
            $element = XMLElement::$authMethodElements['server'][$this->EAPType];
            $objectVariables = get_object_vars($this);
            $outArray = [];
            foreach ($objectVariables as $o => $v) {
                if (in_array($o, $element)) {
                    $outArray[$o] = $v;
                }
            }
            return($outArray);
        }
    }

}

class ServerID extends XMLElement {
    
}

class ClientSideCredential extends XMLElement {

    protected $OuterIdentity;
    protected $UserName;
    protected $Password;
    protected $ClientCertificate;
    protected $Passphrase;
    protected $PAC;
    protected $ProvisionPAC;
    protected $EAPType;


    public function getAll() {
        if (isset(XMLElement::$authMethodElements['client'][$this->EAPType]) && XMLElement::$authMethodElements['client'][$this->EAPType]) {
            $element = XMLElement::$authMethodElements['client'][$this->EAPType];
            $objectVars = get_object_vars($this);
            $outputArray = [];
            foreach ($objectVars as $name => $value) {
                if (in_array($name, $element)) {
                    $outputArray[$name] = $value;
                }
            }
            return($outputArray);
        }
    }

}

class ClientCertificate extends XMLElement {
    
}

class CA extends XMLElement {
    
}

class InnerAuthenticationMethod extends XMLElement {

    protected $EAPMethod;
    protected $NonEAPAuthMethod;
    protected $ServerSideCredential;
    protected $ClientSideCredential;

}

class ProviderInfo extends XMLElement {

    protected $DisplayName;
    protected $Description;
    protected $ProviderLocation;
    protected $ProviderLogo;
    protected $TermsOfUse;
    protected $Helpdesk;

}

class DisplayName extends XMLElement {
    
}

class Description extends XMLElement {
    
}

class ProviderLocation extends XMLElement {

    protected $Longitude;
    protected $Latitude;

}

class ProviderLogo extends XMLElement {
    
}

class TermsOfUse extends XMLElement {
    
}

class Helpdesk extends XMLElement {

    protected $EmailAddress;
    protected $WebAddress;
    protected $Phone;

}

class EmailAddress extends XMLElement {
    
}

class WebAddress extends XMLElement {
    
}

class Phone extends XMLElement {
    
}

/*
  class CompatibleUses  extends XMLElement {
  protected $IEEE80211;
  protected $IEEE8023;
  protected $ABFAB;
  }
  class IEEE80211 extends XMLElement {
  protected $SSID;
  protected $ConsortiumOID;
  protected $MinRSNProto;
  }

  class IEEE8023 extends XMLElement {
  protected $NetworkID;
  }
  class ABFAB extends XMLElement {
  protected $ServiceIdentifier;
  }

 */

/**
 * 
 * @param \SimpleXMLElement $key
 * @param \SimpleXMLElement $value
 */
function SimpleXMLElement_append($key, $value) {
    if (trim((string) $value) == '') {
        $element = $key->addChild($value->getName());
        foreach ($value->attributes() as $attKey => $attValue) {
            $element->addAttribute($attKey, $attValue);
        }
        foreach ($value->children() as $child) {
            SimpleXMLElement_append($element, $child);
        }
    } else {
        $element = $key->addChild($value->getName(), trim((string) $value));
    }
}

/**
 * 
 * @param \SimpleXMLElement $node
 * @param EAPIdentityProvider $object
 * @return void
 */
function marshalObject($node, $object) {
    $qualClassName = get_class($object);
    // remove namespace qualifier
    $pos = strrpos($qualClassName, '\\');
    $className =  substr($qualClassName, $pos + 1);
    $name = preg_replace("/_/", "-", $className);
    if ($object->getValue()) {
        $val = preg_replace('/&/', '&amp;', $object->getValue());
        $childNode = $node->addChild($name, $val);
    } else {
        $childNode = $node->addChild($name);
    }
    if ($object->areAttributes()) {
        $attrs = $object->getAttributes();
        foreach ($attrs as $attrt => $attrv) {
            $childNode->addAttribute($attrt, $attrv);
        }
    }
    $fields = $object->getAll();
    if (empty($fields)) {
        return;
    }
    foreach ($fields as $name => $value) {
        if (is_scalar($value)) {
            $childNode->addChild($name, strval($value));
            continue;
        } 
        if (gettype($value) == 'array') {
            foreach ($value as $insideValue) {
                if (is_object($insideValue)) {
                    marshalObject($childNode, $insideValue);
                }
            }
            continue;
        } 
        if (gettype($value) == 'object') {
            marshalObject($childNode, $value);
        }
    }
}
