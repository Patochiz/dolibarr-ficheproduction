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
 * \file        admin/about.php
 * \ingroup     ficheproduction
 * \brief       About page for module FicheProduction
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT
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

global $langs, $user;

// Libraries
require_once '../lib/ficheproduction.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "ficheproduction@ficheproduction"));

// Access control
if (!$user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

/*
 * Actions
 */

// None

/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans('About'), '', '', 0, 0, '', '', '', 'mod-admin page-about');

$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1').'">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans('About'), $linkback, 'title_setup');

$head = ficheproductionAdminPrepareHead();

print dol_get_fiche_head($head, 'about', $langs->trans('FicheProduction'), -1, "ficheproduction@ficheproduction");

// Module information
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td class="titlefield">'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

// Module version
print '<tr class="oddeven">';
print '<td>'.$langs->trans("ModuleVersion").'</td>';
print '<td><strong>2.0.0</strong></td>';
print '</tr>';

// Compatible Dolibarr version
print '<tr class="oddeven">';
print '<td>'.$langs->trans("DolibarrVersion").'</td>';
print '<td>20.0.0+</td>';
print '</tr>';

// Publisher
print '<tr class="oddeven">';
print '<td>'.$langs->trans("Publisher").'</td>';
print '<td>SuperAdmin</td>';
print '</tr>';

// License
print '<tr class="oddeven">';
print '<td>'.$langs->trans("License").'</td>';
print '<td>GPL v3+</td>';
print '</tr>';

// PHP version
print '<tr class="oddeven">';
print '<td>'.$langs->trans("PHPVersion").'</td>';
print '<td>'.phpversion().' (required: 7.0+)</td>';
print '</tr>';

print '</table>';
print '</div>';

print '<br>';

// Features
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("Features").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="titlefield">🎯 Interface moderne drag & drop</td>';
print '<td>Interface utilisateur intuitive avec glisser-déposer des produits</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>📦 Gestion avancée des colis</td>';
print '<td>Création, modification, duplication, contraintes de poids</td>';
print '</tr>';

print '<tr class="oddeven">'>
print '<td>🔍 Filtrage et recherche</td>';
print '<td>Filtres avancés et recherche instantanée dans l\'inventaire</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>⚖️ Contraintes de poids</td>';
print '<td>Alertes visuelles en temps réel pour le dépassement de poids</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>🔄 Colis multiples</td>';
print '<td>Duplication automatique de colis identiques (×2, ×3, etc.)</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>📱 Responsive design</td>';
print '<td>Interface adaptée desktop, tablette et mobile</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>🏗️ Architecture robuste</td>';
print '<td>Base de données normalisée, API REST, gestion d\'erreurs</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>🎨 Design moderne</td>';
print '<td>Animations fluides, feedback visuel, interface claire</td>';
print '</tr>';

print '</table>';
print '</div>';

print '<br>';

// Technical information
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("TechnicalInformation").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="titlefield">Base de données</td>';
print '<td>3 tables normalisées (session, colis, lignes)</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Frontend</td>';
print '<td>JavaScript ES6, CSS3, HTML5 Drag & Drop API</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Backend</td>';
print '<td>PHP 7.0+, Classes Dolibarr, Hooks système</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>API</td>';
print '<td>Actions AJAX avec authentification token</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Sécurité</td>';
print '<td>Validation des données, permissions utilisateur, tokens CSRF</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>Performance</td>';
print '<td>Chargement asynchrone, cache client, optimisations CSS/JS</td>';
print '</tr>';

print '</table>';
print '</div>';

print '<br>';

// Changelog
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">Changelog v2.0</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="titlefield">✨ Nouvelles fonctionnalités</td>';
print '<td>';
print '• Interface drag & drop moderne<br>';
print '• Gestion des colis multiples<br>';
print '• Contraintes de poids en temps réel<br>';
print '• Filtrage et tri avancé<br>';
print '• Réorganisation des produits<br>';
print '• Responsive design complet';
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>🔧 Améliorations techniques</td>';
print '<td>';
print '• Architecture base de données normalisée<br>';
print '• API REST avec gestion d\'erreurs<br>';
print '• Classes métier complètes<br>';
print '• Validation des données robuste<br>';
print '• Logging et debug intégré<br>';
print '• Performance optimisée';
print '</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>🎨 Interface utilisateur</td>';
print '<td>';
print '• Design moderne et épuré<br>';
print '• Animations fluides<br>';
print '• Feedback visuel en temps réel<br>';
print '• Navigation intuitive<br>';
print '• Notifications système<br>';
print '• Console de debug';
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

print '<br>';

// Support and links
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">Support & Documentation</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="titlefield">📖 Documentation</td>';
print '<td>README.md complet dans le module</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>🐛 Support technique</td>';
print '<td>Issues GitHub pour les bugs et demandes d\'évolution</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>💬 Discussions</td>';
print '<td>GitHub Discussions pour l\'aide à l\'utilisation</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>🔧 Debug</td>';
print '<td>Console intégrée (double-clic sur le titre de l\'interface)</td>';
print '</tr>';

print '</table>';
print '</div>';

print '<br>';

// Installation check
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">État de l\'installation</td>';
print '</tr>';

// Check database tables
$tables_to_check = array(
    'ficheproduction_session',
    'ficheproduction_colis',
    'ficheproduction_colis_line'
);

foreach ($tables_to_check as $table) {
    $sql = "SHOW TABLES LIKE '".MAIN_DB_PREFIX.$table."'";
    $resql = $db->query($sql);
    $exists = ($resql && $db->num_rows($resql) > 0);
    
    print '<tr class="oddeven">';
    print '<td class="titlefield">Table '.$table.'</td>';
    print '<td>'.($exists ? '<span style="color: green;">✓ Créée</span>' : '<span style="color: red;">✗ Manquante</span>').'</td>';
    print '</tr>';
}

// Check CSS file
$css_file = DOL_DOCUMENT_ROOT.'/custom/ficheproduction/css/ficheproduction.css';
print '<tr class="oddeven">';
print '<td>Fichier CSS</td>';
print '<td>'.(file_exists($css_file) ? '<span style="color: green;">✓ Présent</span>' : '<span style="color: red;">✗ Manquant</span>').'</td>';
print '</tr>';

// Check JS file
$js_file = DOL_DOCUMENT_ROOT.'/custom/ficheproduction/js/ficheproduction.js';
print '<tr class="oddeven">';
print '<td>Fichier JavaScript</td>';
print '<td>'.(file_exists($js_file) ? '<span style="color: green;">✓ Présent</span>' : '<span style="color: red;">✗ Manquant</span>').'</td>';
print '</tr>';

// Check permissions
print '<tr class="oddeven">';
print '<td>Permissions utilisateur</td>';
print '<td>';
if ($user->rights->ficheproduction->read) {
    print '<span style="color: green;">✓ Lecture</span> ';
} else {
    print '<span style="color: red;">✗ Lecture</span> ';
}
if ($user->rights->ficheproduction->write) {
    print '<span style="color: green;">✓ Écriture</span> ';
} else {
    print '<span style="color: red;">✗ Écriture</span> ';
}
if ($user->rights->ficheproduction->delete) {
    print '<span style="color: green;">✓ Suppression</span>';
} else {
    print '<span style="color: red;">✗ Suppression</span>';
}
print '</td>';
print '</tr>';

print '</table>';
print '</div>';

print dol_get_fiche_end();

llxFooter();
$db->close();