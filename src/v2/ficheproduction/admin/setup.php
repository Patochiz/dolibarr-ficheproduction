<?php
/* Copyright (C) 2025 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        admin/setup.php
 * \ingroup     ficheproduction
 * \brief       Admin page for module setup
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
    $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
    $i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
    $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
    $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) {
    $res = @include "../main.inc.php";
}
if (!$res && file_exists("../../main.inc.php")) {
    $res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
    $res = @include "../../../main.inc.php";
}
if (!$res) {
    die("Include of main fails");
}

global $langs, $user, $conf;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/ficheproduction.lib.php';

// Translations
$langs->loadLangs(array("admin", "ficheproduction@ficheproduction"));

// Initialize technical objects
$form = new Form($db);

// Access control
if (!$user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09'); // Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'ficheproduction';

$error = 0;
$setupnotempty = 0;

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 1;

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

if (!empty($useFormSetup)) {
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
    $formSetup = new FormSetup($db);

    // Setup conf FICHEPRODUCTION_*
    $item = $formSetup->newItem('FICHEPRODUCTION_POIDS_MAX_COLIS');
    $item->setAsString();
    $item->defaultFieldValue = '25';
    $item->nameText = $langs->trans('DefaultMaxWeight');
    $item->helpText = $langs->trans('DefaultMaxWeight').' (kg)';
    $item->fieldAttr = array('placeholder' => '25');

    $item = $formSetup->newItem('FICHEPRODUCTION_AUTO_CREATE_SESSION');
    $item->setAsYesNo();
    $item->defaultFieldValue = '1';
    $item->nameText = $langs->trans('AutoCreateSession');
    $item->helpText = $langs->trans('AutoCreateSession');

    $setupnotempty += count($formSetup->items);
}

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

/*
 * Actions
 */

// For retrocompatibility Dolibarr < 15.0
if (versioncompare(explode('.', DOL_VERSION), array(15)) < 0) {
    include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';
} else {
    include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';
}

if ($action == 'updateMask') {
    $maskconst = GETPOST('maskconst', 'aZ09');
    $maskvalue = GETPOST('maskvalue', 'alpha');

    if ($maskconst && preg_match('/_MASK$/', $maskconst)) {
        $res = dolibarr_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', $conf->entity);
        if (!($res > 0)) {
            $error++;
        }
    }

    if (!$error) {
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    } else {
        setEventMessages($langs->trans("Error"), null, 'errors');
    }
} elseif ($action == 'specimen') {
    $modele = GETPOST('module', 'alpha');
    $tmpobjectkey = GETPOST('object');

    $tmpobject = new $tmpobjectkey($db);
    $tmpobject->initAsSpecimen();

    // Search template files
    $file = ''; $classname = ''; $filefound = 0;
    $dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
    foreach ($dirmodels as $reldir) {
        $file = dol_buildpath($reldir."core/modules/ficheproduction/doc/pdf_".$modele."_".strtolower($tmpobjectkey).".modules.php", 0);
        if (file_exists($file)) {
            $filefound = 1;
            $classname = "pdf_".$modele;
            break;
        }
    }

    if ($filefound) {
        require_once $file;

        $module = new $classname($db);

        if ($module->write_file($tmpobject, $langs) > 0) {
            header("Location: ".DOL_URL_ROOT."/document.php?modulepart=".$tmpobjectkey."&file=SPECIMEN.pdf");
            return;
        } else {
            setEventMessages($module->error, null, 'errors');
            dol_syslog($module->error, LOG_ERR);
        }
    } else {
        setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
        dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
    }
} elseif ($action == 'set') {
    // Activate a model
    $ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
    $ret = delDocumentModel($value, $type);
    if ($ret > 0) {
        if ($conf->global->FICHEPRODUCTION_ADDON_PDF == "$value") {
            dolibarr_del_const($db, 'FICHEPRODUCTION_ADDON_PDF', $conf->entity);
        }
    }
} elseif ($action == 'setdoc') {
    // Set or unset default model
    if (dolibarr_set_const($db, "FICHEPRODUCTION_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
        // The constant that was read before the new set
        // We therefore requires a variable to have a coherent view
        $conf->global->FICHEPRODUCTION_ADDON_PDF = $value;
    }

    // On active le modele
    $ret = delDocumentModel($value, $type);
    if ($ret > 0) {
        $ret = addDocumentModel($value, $type, $label, $scandir);
    }
}

/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans('FicheProductionSetup'), '', '', 0, 0, '', '', '', 'mod-admin page-setup');

$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans('FicheProductionSetup'), $linkback, 'title_setup');

$head = ficheproductionAdminPrepareHead();

print dol_get_fiche_head($head, 'settings', $langs->trans('FicheProduction'), -1, "ficheproduction@ficheproduction");

if ($action == 'edit') {
    print $formSetup->generateOutput(true);
    print '<br>';
} elseif (!empty($formSetup) && $setupnotempty) {
    print $formSetup->generateOutput();
    print '<div class="tabsAction">';
    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
    print '</div>';
} else {
    print '<br>'.$langs->trans("NothingToSetup");
}

/*
 * Document templates generators
 */
if (!empty($setupnotempty)) {
    print '<br>';
}

/*
print load_fiche_titre($langs->trans('DocumentModels', $langs->transnoentitiesnoconv('FicheProduction')), '', '');

// Define array def of document models
$def = array();
$sql = "SELECT nom";
$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
$sql .= " WHERE type = '".$db->escape($type)."'";
$sql .= " AND entity = ".$conf->entity;
$resql = $db->query($sql);
if ($resql) {
    $i = 0;
    $num_rows = $db->num_rows($resql);
    while ($i < $num_rows) {
        $array = $db->fetch_array($resql);
        array_push($def, $array[0]);
        $i++;
    }
} else {
    dol_print_error($db);
}

print "<table class=\"noborder\" width=\"100%\">\n";
print "<tr class=\"liste_titre\">\n";
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center" width="60">'.$langs->trans("Status").'</td>';
print '<td class="center" width="60">'.$langs->trans("Default").'</td>';
print '<td class="center" width="38">'.$langs->trans("ShortInfo").'</td>';
print '<td class="center" width="38">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
    foreach (array('', '/doc') as $valdir) {
        $realpath = $reldir."core/modules/ficheproduction".$valdir;
        $dir = dol_buildpath($realpath);

        if (is_dir($dir)) {
            $handle = opendir($dir);
            if (is_resource($handle)) {
                while (($file = readdir($handle)) !== false) {
                    $filelist[] = $file;
                }
                closedir($handle);
                arsort($filelist);

                foreach ($filelist as $file) {
                    if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
                        if (file_exists($dir.'/'.$file)) {
                            $name = substr($file, 4, dol_strlen($file) - 16);
                            $classname = substr($file, 0, dol_strlen($file) - 12);

                            require_once $dir.'/'.$file;
                            $module = new $classname($db);

                            $modulequalified = 1;
                            if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
                                $modulequalified = 0;
                            }
                            if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
                                $modulequalified = 0;
                            }

                            if ($modulequalified) {
                                print '<tr class="oddeven"><td width="100">';
                                print (empty($module->name) ? $name : $module->name);
                                print "</td><td>\n";
                                if (method_exists($module, 'info')) {
                                    print $module->info($langs);
                                } else {
                                    print $module->description;
                                }
                                print '</td>';

                                // Active
                                if (in_array($name, $def)) {
                                    print '<td class="center">'."\n";
                                    print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=del&token='.newToken().'&value='.urlencode($name).'">';
                                    print img_picto($langs->trans("Enabled"), 'switch_on');
                                    print '</a>';
                                    print '</td>';
                                } else {
                                    print '<td class="center">'."\n";
                                    print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=set&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
                                    print "</td>";
                                }

                                // Default
                                print '<td class="center">';
                                if ($conf->global->FICHEPRODUCTION_ADDON_PDF == $name) {
                                    print img_picto($langs->trans("Default"), 'on');
                                } else {
                                    print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setdoc&token='.newToken().'&value='.urlencode($name).'&scan_dir='.urlencode($module->scandir).'&label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
                                }
                                print '</td>';

                                // Info
                                $htmltooltip = ''.$langs->trans("Name").': '.$module->name;
                                $htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
                                if ($module->type == 'pdf') {
                                    $htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
                                }
                                $htmltooltip .= '<br>'.$langs->trans("Path").': '.preg_replace('/^'.preg_quote(DOL_DOCUMENT_ROOT, '/').'/', '', $realpath).'/'.$file;

                                $htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
                                $htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
                                $htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);

                                print '<td class="center">';
                                print $form->textwithpicto('', $htmltooltip, 1, 0);
                                print '</td>';

                                // Preview
                                print '<td class="center">';
                                if ($module->type == 'pdf') {
                                    print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'&object='.$tmpobjectkey.'&token='.newToken().'">'.img_object($langs->trans("Preview"), 'pdf').'</a>';
                                } else {
                                    print img_object($langs->trans("PreviewNotAvailable"), 'generic');
                                }
                                print '</td>';

                                print "</tr>\n";
                            }
                        }
                    }
                }
            }
        }
    }
}

print '</table><br>';
*/

print dol_get_fiche_end();

llxFooter();
$db->close();