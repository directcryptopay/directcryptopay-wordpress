# 🚀 Installation Rapide - Plugin WordPress DirectCryptoPay

## 📥 Téléchargement

Téléchargez le plugin depuis votre Dashboard DirectCryptoPay :
**Dashboard → Plugins & Integrations → WordPress → Download Plugin**

Ou directement : https://test-app.directcryptopay.com/downloads/directcryptopay-wordpress.zip

## 📦 Installation

### Méthode 1 : Via l'interface WordPress (Recommandé)

1. Connectez-vous à votre **panneau d'administration WordPress**
2. Allez dans **Extensions** → **Ajouter**
3. Cliquez sur **Téléverser une extension**
4. Cliquez sur **Choisir un fichier**
5. Sélectionnez le fichier `directcryptopay-wordpress.zip`
6. Cliquez sur **Installer maintenant**
7. Une fois installé, cliquez sur **Activer l'extension**

### Méthode 2 : Via FTP

1. Décompressez le fichier `directcryptopay-wordpress.zip`
2. Uploadez le dossier `wordpress-plugin` vers `/wp-content/plugins/`
3. Renommez-le en `directcryptopay`
4. Allez dans **Extensions** et activez **DirectCryptoPay**

## ⚙️ Configuration

1. Dans le menu WordPress, cliquez sur **DirectCryptoPay** (💰)
2. Copiez votre **Integration ID** depuis votre [Dashboard DirectCryptoPay](https://test-app.directcryptopay.com/dashboard/integrations)
3. Collez-le dans le champ **Integration ID**
4. Vérifiez l'**API URL** :
   - Testnet : `https://test-api.directcryptopay.com`
   - Production : `https://api.directcryptopay.com`
5. Cliquez sur **Enregistrer les modifications**

## 🎯 Utilisation

### Shortcode (Simple et rapide)

Ajoutez ce shortcode dans n'importe quelle page, article ou widget :

```
[dcp_pay amount="10" label="Payer avec Crypto"]
```

**Paramètres disponibles :**

- `amount` - Montant en USD (obligatoire)
- `label` - Texte du bouton (défaut : "Pay with Crypto")
- `currency` - Devise d'affichage (défaut : USD)
- `return_url` - URL de redirection après paiement (optionnel)

**Exemples :**

```
[dcp_pay amount="5" label="Faire un don"]
```

```
[dcp_pay amount="99" label="Acheter Premium" currency="EUR"]
```

```
[dcp_pay amount="25" label="S'abonner" return_url="https://monsite.com/merci"]
```

### Bloc Gutenberg (Éditeur visuel)

1. Dans l'éditeur de page, cliquez sur le **+** pour ajouter un bloc
2. Cherchez "**DirectCryptoPay Payment Button**"
3. Configurez le montant, le label et la devise dans les paramètres du bloc
4. Publiez votre page

## 🎨 Personnalisation

Le bouton de paiement utilise le style par défaut de DirectCryptoPay. Vous pouvez personnaliser l'apparence en ajoutant du CSS personnalisé dans votre thème :

```css
.dcp-payment-button {
  /* Vos styles personnalisés */
}
```

## 🔍 Vérification

Pour vérifier que le plugin fonctionne correctement :

1. Créez une page de test
2. Ajoutez le shortcode : `[dcp_pay amount="1" label="Test"]`
3. Publiez et visualisez la page
4. Le bouton DirectCryptoPay devrait apparaître
5. Cliquez dessus pour ouvrir la modal de paiement

## 🆘 Support

- **Documentation** : https://docs.directcryptopay.com
- **Dashboard** : https://app.directcryptopay.com
- **Support** : support@directcryptopay.com

## ✅ Checklist d'Installation

- [ ] Plugin téléchargé
- [ ] Plugin installé et activé dans WordPress
- [ ] Integration ID configuré dans les réglages
- [ ] Page de test créée avec le shortcode
- [ ] Bouton de paiement visible et fonctionnel
- [ ] Modal DirectCryptoPay s'ouvre correctement

## 🔄 Mise à Jour

Pour mettre à jour le plugin :

1. Téléchargez la dernière version depuis votre Dashboard
2. Désactivez le plugin actuel
3. Supprimez l'ancienne version
4. Installez la nouvelle version
5. Réactivez le plugin
6. Vérifiez que vos réglages sont toujours présents

**Note** : Vos réglages (Integration ID) sont conservés dans la base de données WordPress.

## 🌟 Version

- **Version actuelle** : 1.0.0
- **Compatibilité WordPress** : 5.0+
- **Compatibilité PHP** : 7.4+
- **Testé jusqu'à** : WordPress 6.4

---

Développé avec ❤️ par [DirectCryptoPay](https://directcryptopay.com)
