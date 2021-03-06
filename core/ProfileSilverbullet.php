<?php

/*
 * ******************************************************************************
 * Copyright 2011-2017 DANTE Ltd. and GÉANT on behalf of the GN3, GN3+, GN4-1 
 * and GN4-2 consortia
 *
 * License: see the web/copyright.php file in the file structure
 * ******************************************************************************
 */

/**
 * This file contains the ProfileSilverbullet class.
 *
 * @author Stefan Winter <stefan.winter@restena.lu>
 * @author Tomasz Wolniewicz <twoln@umk.pl>
 *
 * @package Developer
 *
 */

namespace core;

use \Exception;

/**
 * Silverbullet (marketed as "Managed IdP") is a RADIUS profile which 
 * corresponds directly to a built-in RADIUS server and CA. 
 * It provides all functions needed for a admin-side web interface where users
 * can be added and removed, and new devices be enabled.
 * 
 * When downloading a Silverbullet based profile, the profile includes per-user
 * per-device client certificates which can be immediately used to log into 
 * eduroam.
 *
 * @author Stefan Winter <stefan.winter@restena.lu>
 * @author Tomasz Wolniewicz <twoln@umk.pl>
 *
 * @license see LICENSE file in root directory
 *
 * @package Developer
 */
class ProfileSilverbullet extends AbstractProfile {

    const SB_ACKNOWLEDGEMENT_REQUIRED_DAYS = 365;

    public $termsAndConditions;

    /*
     * 
     */

    const PRODUCTNAME = "Managed IdP";

    /**
     * Class constructor for existing profiles (use IdP::newProfile() to actually create one). Retrieves all attributes and 
     * supported EAP types from the DB and stores them in the priv_ arrays.
     * 
     * @param int $profileId identifier of the profile in the DB
     * @param IdP $idpObject optionally, the institution to which this Profile belongs. Saves the construction of the IdP instance. If omitted, an extra query and instantiation is executed to find out.
     */
    public function __construct($profileId, $idpObject = NULL) {
        parent::__construct($profileId, $idpObject);

        $this->entityOptionTable = "profile_option";
        $this->entityIdColumn = "profile_id";
        $this->attributes = [];

        $tempMaxUsers = 200; // abolutely last resort fallback if no per-fed and no config option
// set to global config value

        if (isset(CONFIG_CONFASSISTANT['SILVERBULLET']['default_maxusers'])) {
            $tempMaxUsers = CONFIG_CONFASSISTANT['SILVERBULLET']['default_maxusers'];
        }
        $myInst = new IdP($this->institution);
        $myFed = new Federation($myInst->federation);
        $fedMaxusers = $myFed->getAttributes("fed:silverbullet-maxusers");
        if (isset($fedMaxusers[0])) {
            $tempMaxUsers = $fedMaxusers[0]['value'];
        }

// realm is automatically calculated, then stored in DB

        $this->realm = "opaquehash@$myInst->identifier-$this->identifier." . strtolower($myInst->federation) . CONFIG_CONFASSISTANT['SILVERBULLET']['realm_suffix'];
        $localValueIfAny = "";

// but there's some common internal attributes populated directly
        $internalAttributes = [
            "internal:profile_count" => $this->idpNumberOfProfiles,
            "internal:realm" => preg_replace('/^.*@/', '', $this->realm),
            "internal:use_anon_outer" => FALSE,
            "internal:checkuser_outer" => TRUE,
            "internal:checkuser_value" => "anonymous",
            "internal:anon_local_value" => $localValueIfAny,
            "internal:silverbullet_maxusers" => $tempMaxUsers,
            "profile:production" => "on",
        ];

// and we need to populate eap:server_name and eap:ca_file with the NRO-specific EAP information
        $silverbulletAttributes = [
            "eap:server_name" => "auth." . strtolower($myFed->tld) . CONFIG_CONFASSISTANT['SILVERBULLET']['server_suffix'],
        ];
        $x509 = new \core\common\X509();
        $caHandle = fopen(dirname(__FILE__) . "/../config/SilverbulletServerCerts/" . strtoupper($myFed->tld) . "/root.pem", "r");
        if ($caHandle !== FALSE) {
            $cAFile = fread($caHandle, 16000000);
            $silverbulletAttributes["eap:ca_file"] = $x509->der2pem(($x509->pem2der($cAFile)));
        }

        $temp = array_merge($this->addInternalAttributes($internalAttributes), $this->addInternalAttributes($silverbulletAttributes));
        $tempArrayProfLevel = array_merge($this->addDatabaseAttributes(), $temp);

// now, fetch and merge IdP-wide attributes

        $this->attributes = $this->levelPrecedenceAttributeJoin($tempArrayProfLevel, $this->idpAttributes, "IdP");

        $this->privEaptypes = $this->fetchEAPMethods();

        $this->name = ProfileSilverbullet::PRODUCTNAME;

        $this->loggerInstance->debug(3, "--- END Constructing new Profile object ... ---\n");

        $this->termsAndConditions = "<h2>Product Definition</h2>
        <p>" . \core\ProfileSilverbullet::PRODUCTNAME . " outsources the technical setup of " . CONFIG_CONFASSISTANT['CONSORTIUM']['display_name'] . " " . CONFIG_CONFASSISTANT['CONSORTIUM']['nomenclature_institution'] . " functions to the " . CONFIG_CONFASSISTANT['CONSORTIUM']['display_name'] . " Operations Team. The system includes</p>
            <ul>
                <li>a web-based user management interface where user accounts and access credentials can be created and revoked (there is a limit to the number of active users)</li>
                <li>a technical infrastructure ('CA') which issues and revokes credentials</li>
                <li>a technical infrastructure ('RADIUS') which verifies access credentials and subsequently grants access to " . CONFIG_CONFASSISTANT['CONSORTIUM']['display_name'] . "</li>
                <li><span style='color: red;'>TBD: a lookup/notification system which informs you of network abuse complaints by " . CONFIG_CONFASSISTANT['CONSORTIUM']['display_name'] . " Service Providers that pertain to your users</span></li>
            </ul>
        <h2>User Account Liability</h2>
        <p>As an " . CONFIG_CONFASSISTANT['CONSORTIUM']['display_name'] . " " . CONFIG_CONFASSISTANT['CONSORTIUM']['nomenclature_institution'] . " administrator using this system, you are authorized to create user accounts according to your local " . CONFIG_CONFASSISTANT['CONSORTIUM']['nomenclature_institution'] . " policy. You are fully responsible for the accounts you issue and are the data controller for all user information you deposit in this system; the system is a data processor.</p>";
        $this->termsAndConditions .= "<p>Your responsibilities include that you</p>
        <ul>
            <li>only issue accounts to members of your " . CONFIG_CONFASSISTANT['CONSORTIUM']['nomenclature_institution'] . ", as defined by your local policy.</li>
            <li>must make sure that all accounts that you issue can be linked by you to actual human end users</li>
            <li>have to immediately revoke accounts of users when they leave or otherwise stop being a member of your " . CONFIG_CONFASSISTANT['CONSORTIUM']['nomenclature_institution'] . "</li>
            <li>will act upon notifications about possible network abuse by your users and will appropriately sanction them</li>
        </ul>
        <p>";
        $this->termsAndConditions .= "Failure to comply with these requirements may make your " . CONFIG_CONFASSISTANT['CONSORTIUM']['nomenclature_federation'] . " act on your behalf, which you authorise, and will ultimately lead to the deletion of your " . CONFIG_CONFASSISTANT['CONSORTIUM']['nomenclature_institution'] . " (and all the users you create inside) in this system.";
        $this->termsAndConditions .= "</p>
        <h2>Privacy</h2>
        <p>With " . \core\ProfileSilverbullet::PRODUCTNAME . ", we are necessarily storing personally identifiable information about the end users you create. While the actual human is only identifiable with your help, we consider all the user data as relevant in terms of privacy jurisdiction. Please note that</p>
        <ul>
            <li>You are the only one who needs to be able to make a link to the human behind the usernames you create. The usernames you create in the system have to be rich enough to allow you to make that identification step. Also consider situations when you are unavailable or leave the organisation and someone else needs to perform the matching to an individual.</li>
            <li>The identifiers we create in the credentials are not linked to the usernames you add to the system; they are randomly generated pseudonyms.</li>
            <li>Each access credential carries a different pseudonym, even if it pertains to the same username.</li>
            <li>If you choose to deposit users' email addresses in the system, you authorise the system to send emails on your behalf regarding operationally relevant events to the users in question (e.g. notification of nearing expiry dates of credentials, notification of access revocation).
        </ul>";
    }
    
    /**
     * Updates database with new installer location; NOOP because we do not
     * cache anything in Silverbullet
     * 
     * @param string $device the device identifier string
     * @param string $path the path where the new installer can be found
     * @param string $mime the mime type of the new installer
     * @param int $integerEapType the inter-representation of the EAP type that is configured in this installer
     */
    public function updateCache($device, $path, $mime, $integerEapType) {
        // caching is not supported in SB (private key in installers)
        // the following merely makes the "unused parameter" warnings go away
        // the FALSE in condition one makes sure it never gets executed
        if (FALSE || $device == "Macbeth" || $path == "heath" || $mime == "application/witchcraft" || $integerEapType == 0) {
            throw new Exception("FALSE is TRUE, and TRUE is FALSE! Hover through the browser and filthy code!");
        }
    }

    /**
     * register new supported EAP method for this profile
     *
     * @param \core\common\EAP $type The EAP Type, as defined in class EAP
     * @param int $preference preference of this EAP Type. If a preference value is re-used, the order of EAP types of the same preference level is undefined.
     *
     */
    public function addSupportedEapMethod(\core\common\EAP $type, $preference) {
        // the parameters really should only list SB and with prio 1 - otherwise,
        // something fishy is going on
        if ($type->getIntegerRep() != \core\common\EAP::INTEGER_SILVERBULLET || $preference != 1) {
            throw new Exception("Silverbullet::addSupportedEapMethod was called for a non-SP EAP type or unexpected priority!");
        }
        parent::addSupportedEapMethod($type, 1);
    }

    /**
     * It's EAP-TLS and there is no point in anonymity
     * @param boolean $shallwe
     */
    public function setAnonymousIDSupport($shallwe) {
        // we don't do anonymous outer IDs in SB
        if ($shallwe === TRUE) {
            throw new Exception("Silverbullet: attempt to add anonymous outer ID support to a SB profile!");
        }
        $this->databaseHandle->exec("UPDATE profile SET use_anon_outer = 0 WHERE profile_id = $this->identifier");
    }

    /**
     * performs an HTTP request. Currently unused, will be for external CA API calls.
     * 
     * @param string $url the URL to send the request to
     * @param array $postValues POST values to send
     * @return string the returned HTTP content
     */
    private function httpRequest($url, $postValues) {
        $options = [
            'http' => ['header' => 'Content-type: application/x-www-form-urlencoded\r\n', "method" => 'POST', 'content' => http_build_query($postValues)]
        ];
        $context = stream_context_create($options);
        return file_get_contents($url, false, $context);
    }

    /**
     * find out about the status of a given SB user; retrieves the info regarding all his tokens (and thus all his certificates)
     * @param int $userId
     * @return array of invitationObjects
     */
    public function userStatus($userId) {
        $retval = [];
        $userrows = $this->databaseHandle->exec("SELECT `token` FROM `silverbullet_invitation` WHERE `silverbullet_user_id` = ? AND `profile_id` = ? ", "ii", $userId, $this->identifier);
        // SELECT -> resource, not boolean
        while ($returnedData = mysqli_fetch_object(/** @scrutinizer ignore-type */ $userrows)) {
            $retval[] = new SilverbulletInvitation($returnedData->token);
        }
        return $retval;
    }

    /**
     * finds out the expiry date of a given user
     * @param int $userId
     * @return string
     */
    public function getUserExpiryDate($userId) {
        $query = $this->databaseHandle->exec("SELECT expiry FROM silverbullet_user WHERE id = ? AND profile_id = ? ", "ii", $userId, $this->identifier);
        // SELECT -> resource, not boolean
        while ($returnedData = mysqli_fetch_object(/** @scrutinizer ignore-type */ $query)) {
            return $returnedData->expiry;
        }
    }
    
    /**
     * sets the expiry date of a user to a new date of choice
     * @param int $userId
     * @param \DateTime $date
     */
    public function setUserExpiryDate($userId, $date) {
        $query = "UPDATE silverbullet_user SET expiry = ? WHERE profile_id = ? AND id = ?";
        $theDate = $date->format("Y-m-d");
        $this->databaseHandle->exec($query, "sii", $theDate, $this->identifier, $userId);
    }

    /**
     * lists all users of this SB profile
     * @return array
     */
    public function listAllUsers() {
        $userArray = [];
        $users = $this->databaseHandle->exec("SELECT `id`, `username` FROM `silverbullet_user` WHERE `profile_id` = ? ", "i", $this->identifier);
        // SELECT -> resource, not boolean
        while ($res = mysqli_fetch_object(/** @scrutinizer ignore-type */ $users)) {
            $userArray[$res->id] = $res->username;
        }
        return $userArray;
    }

    /**
     * lists all users which are currently active (i.e. have pending invitations and/or valid certs)
     * @return array
     */
    public function listActiveUsers() {
        // users are active if they have a non-expired invitation OR a non-expired, non-revoked certificate
        $userCount = [];
        $users = $this->databaseHandle->exec("SELECT DISTINCT u.id AS usercount FROM silverbullet_user u, silverbullet_invitation i, silverbullet_certificate c "
                . "WHERE u.profile_id = ? "
                . "AND ( "
                . "( u.id = i.silverbullet_user_id AND i.expiry >= NOW() )"
                . "     OR"
                . "  ( u.id = c.silverbullet_user_id AND c.expiry >= NOW() AND c.revocation_status != 'REVOKED' ) "
                . ")", "i", $this->identifier);
        // SELECT -> resource, not boolean
        while ($res = mysqli_fetch_object(/** @scrutinizer ignore-type */ $users)) {
            $userCount[] = $res->usercount;
        }
        return $userCount;
    }

    /**
     * adds a new user to the profile
     * 
     * @param string $username
     * @param \DateTime $expiry
     * @return int row ID of the new user in the database
     */
    public function addUser($username, \DateTime $expiry) {
        $query = "INSERT INTO silverbullet_user (profile_id, username, expiry) VALUES(?,?,?)";
        $date = $expiry->format("Y-m-d");
        $this->databaseHandle->exec($query, "iss", $this->identifier, $username, $date);
        return $this->databaseHandle->lastID();
    }

    /**
     * revoke all active certificates and pending invitations of a user
     * @param int $userId
     */
    public function deactivateUser($userId) {
        // set the expiry date of any still valid invitations to NOW()
        $query = "SELECT id FROM silverbullet_invitation WHERE profile_id = $this->identifier AND silverbullet_user_id = ? AND expiry >= NOW()";
        $exec = $this->databaseHandle->exec($query, "i", $userId);
        // SELECT -> resource, not boolean
        while ($result = mysqli_fetch_object(/** @scrutinizer ignore-type */ $exec)) {
            $invitation = new SilverbulletInvitation($result->id);
            $invitation->revokeInvitation();
        }
        // and revoke all certificates
        $query2 = "SELECT serial_number FROM silverbullet_certificate WHERE profile_id = $this->identifier AND silverbullet_user_id = ? AND expiry >= NOW() AND revocation_status = 'NOT_REVOKED'";
        $exec2 = $this->databaseHandle->exec($query2, "i", $userId);
        // SELECT -> resource, not boolean
        while ($result = mysqli_fetch_object(/** @scrutinizer ignore-type */ $exec2)) {
            $certObject = new SilverbulletCertificate($result->serial_number);
            $certObject->revokeCertificate();
        }
        // and finally set the user expiry date to NOW(), too
        $query3 = "UPDATE silverbullet_user SET expiry = NOW() WHERE profile_id = $this->identifier AND id = ?";
        $this->databaseHandle->exec($query3, "i", $userId);
    }
    
    /**
     * updates the last_ack for all users (invoked when the admin claims to have re-verified continued eligibility of all users)
     */
    public function refreshEligibility() {
        $query = "UPDATE silverbullet_user SET last_ack = NOW() WHERE profile_id = ?";
        $this->databaseHandle->exec($query, "i", $this->identifier);
    }
}
