<?php

/*
 * ******************************************************************************
 * Copyright 2011-2017 DANTE Ltd. and GÉANT on behalf of the GN3, GN3+, GN4-1 
 * and GN4-2 consortia
 *
 * License: see the web/copyright.php file in the file structure
 * ******************************************************************************
 */

$out = "<h3>" . _("Access the sources") . "</h3>" .
        sprintf(_("%s is an opensource project. If you are interested in the details of the implementation, please visit GitHub."), CONFIG['APPEARANCE']['productname']) .
        "<h3>" . _("Join the developpers mailing list.") . "</h3>" .
        sprintf(_("The list is availabe at: %s"), CONFIG['APPEARANCE']['support-contact']['display']) .
        "<h3>" . _("Add a translation") . "</h3>" .
        _("If you waould like to add a new languege to CAT then please contact us ...");
        

