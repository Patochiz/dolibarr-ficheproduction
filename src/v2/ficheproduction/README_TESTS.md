# 🧪 Guide de Tests - FicheProduction v2.0 Modulaire

## 🚨 Problème Identifié

**Symptômes observés :**
- ✅ Le tableau d'en-tête se charge
- ❌ L'encart d'adresse de livraison est vide
- ❌ Plus rien ne se charge après
- ❌ Aucun message d'erreur visible

## 🔍 Diagnostic

### Causes Probables

1. **Erreur PHP fatale** dans le module `ficheproduction-display.php`
2. **Variable `$db` non accessible** dans `getDeliveryInformation()`
3. **Modules JavaScript non chargés** correctement
4. **Erreurs supprimées** par la configuration de production

## 🛠️ Solutions Fournies

### 1. **Fichiers de Diagnostic**

#### A. `debug-test.php` - Script de diagnostic complet
```bash
# Aller dans le dossier du projet
cd src/v2/ficheproduction/

# Tester le diagnostic
php debug-test.php
```

#### B. `ficheproduction-test.php` - Version avec debug activé
```bash
# Utiliser cette version pour voir les erreurs PHP
# Remplace temporairement ficheproduction.php
```

#### C. `test-modules.html` - Test des modules JavaScript
```bash
# Ouvrir dans un navigateur pour tester les modules JS
open test-modules.html
```

### 2. **Version Corrigée du Module Display**

- **Fichier :** `includes/ficheproduction-display-fixed.php`
- **Corrections :**
  - Variable `$db` passée correctement
  - Gestion d'erreurs ajoutée
  - Protection contre les objets null
  - Échappement HTML pour la sécurité

## 📋 Procédure de Test

### Étape 1: Tests de Base

```bash
# 1. Tester le diagnostic
cd src/v2/ficheproduction/
php debug-test.php

# Regarder la sortie pour identifier les problèmes
```

### Étape 2: Version de Test

```bash
# 2. Sauvegarder l'original
mv ficheproduction.php ficheproduction-original.php

# 3. Utiliser la version de test
mv ficheproduction-test.php ficheproduction.php

# 4. Tester dans le navigateur
# Les erreurs PHP seront affichées
```

### Étape 3: Module Display Corrigé

```bash
# 5. Remplacer le module display défaillant
mv includes/ficheproduction-display.php includes/ficheproduction-display-original.php
mv includes/ficheproduction-display-fixed.php includes/ficheproduction-display.php
```

### Étape 4: Test JavaScript

```bash
# 6. Ouvrir le testeur JavaScript
open test-modules.html
# Vérifier que tous les modules se chargent correctement
```

## 🔧 Corrections Appliquées

### 1. **Gestion de la Variable `$db`**
```php
// AVANT (incorrect)
function displaySummarySection($object, $langs, $userCanEdit) {
    displayDeliveryInfo($object, $langs, $db); // $db non accessible
}

// APRÈS (correct)
function displaySummarySection($object, $langs, $userCanEdit, $db) {
    displayDeliveryInfo($object, $langs, $db); // $db passé en paramètre
}
```

### 2. **Protection contre les Erreurs**
```php
// AVANT
$contacts = $object->liste_contact(-1, 'external', 0, 'SHIPPING');

// APRÈS
try {
    if (!is_object($object) || empty($object->id)) {
        return $deliveryInfo;
    }
    $contacts = $object->liste_contact(-1, 'external', 0, 'SHIPPING');
} catch (Exception $e) {
    error_log("Erreur getDeliveryInformation: " . $e->getMessage());
}
```

### 3. **Échappement HTML**
```php
// AVANT
print $deliveryInfo['contact'];

// APRÈS
print dol_escape_htmltag($deliveryInfo['contact']);
```

## 📊 Checklist de Validation

### Tests PHP
- [ ] `debug-test.php` s'exécute sans erreur
- [ ] Tous les modules PHP se chargent
- [ ] Toutes les fonctions sont définies
- [ ] La version de test affiche la page complète

### Tests JavaScript
- [ ] `test-modules.html` montre tous les modules chargés
- [ ] Namespace `FicheProduction` existe
- [ ] Toutes les fonctions globales sont disponibles
- [ ] Console de debug fonctionne

### Tests Fonctionnels
- [ ] Page se charge complètement
- [ ] Tableau d'en-tête affiché
- [ ] Encart d'adresse de livraison rempli
- [ ] Interface principale visible
- [ ] Modules JavaScript initialisés

## 🚨 Actions d'Urgence

### Si les tests échouent encore :

1. **Revenir à l'original :**
```bash
mv ficheproduction.php ficheproduction-test.php
mv ficheproduction-original.php ficheproduction.php
```

2. **Vérifier les logs Dolibarr :**
```bash
# Chercher dans les logs Dolibarr
tail -f /path/to/dolibarr/logs/dolibarr.log
```

3. **Vérifier les logs Apache/Nginx :**
```bash
# Logs d'erreur du serveur web
tail -f /var/log/apache2/error.log
# ou
tail -f /var/log/nginx/error.log
```

## 💡 Conseils de Débogage

### 1. **Variables PHP**
```php
// Ajouter temporairement pour déboguer
var_dump($object->thirdparty);
exit;
```

### 2. **Console JavaScript**
```javascript
// Dans la console du navigateur
console.log(window.FicheProduction);

// Vérifier les erreurs
window.addEventListener('error', function(e) {
    console.error('Erreur:', e.message, e.filename, e.lineno);
});
```

### 3. **Network Tab**
- Ouvrir F12 → Network
- Vérifier que tous les fichiers JS se chargent (200 OK)
- Regarder s'il y a des erreurs 404 ou 500

## 📞 Support

Si le problème persiste après ces tests :

1. **Envoyer les résultats de :**
   - `debug-test.php`
   - `test-modules.html`
   - Logs d'erreur du serveur

2. **Informations système :**
   - Version PHP
   - Version Dolibarr
   - Navigateur utilisé

3. **Captures d'écran :**
   - Console JavaScript (F12)
   - Page résultat
   - Messages d'erreur éventuels

---

**🎯 Objectif :** Identifier et corriger rapidement le problème de chargement pour valider la restructuration modulaire.