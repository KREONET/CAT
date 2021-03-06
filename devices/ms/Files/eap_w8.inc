; ******************************************************************************
; * Copyright 2011-2017 DANTE Ltd. and GÉANT on behalf of the GN3, GN3+, GN4-1 
; * and GN4-2 consortia
; *
; * License: see the web/copyright.php file in the file structure
; ******************************************************************************

!include "common.nsh"
;===========================
;TRANSLATION
Section "<?php echo _("Installation of the personal certificate")?>" cert
!insertmacro MUI_HEADER_TEXT "<?php echo _("Components installation")?>" "<?php echo _("Personal certificate")?>"
SectionEnd
;==========================
;TRANSLATION
Section "<?php echo _("Installation of network profiles")?>" "<?php echo _("profiles")?>"
SectionIn RO
!insertmacro MUI_HEADER_TEXT "<?php echo _("Installation")?>" "<?php echo _("Network profiles update")?>"
File "w8/*lan_prof*.xml"


${If} $wireless_result == 0
Call CheckWirelessProfiles
Call CreateWirelessProfiles 
${EndIf}

${If} $wired == 1
  Call CreateLanProfile
${EndIf}
!ifdef CRED
  Call InstallUserCredentials
!endif
;Sleep 2000
SendMessage $HWNDPARENT "0x408" "1" ""
Sleep 2000
SectionEnd
;==========================
Section "-koncowka"
!ifdef CA_CERT
Delete "$TEMP\${CA_CERT}"
!endif
!ifdef EXTERNAL_INFO
  Call displayExternalInfo
!endif
SectionEnd
;==========================
