# API des Produits - Click and Ship

## 📋 Vue d'ensemble

L'API des produits permet de gérer le catalogue de produits de Click and Ship. Elle utilise API Platform pour générer automatiquement les endpoints REST.

## 🔐 Sécurité

- **Lecture** : Toutes les routes GET sont publiques
- **Écriture** : Les routes POST, PUT, DELETE nécessitent le rôle `ROLE_ADMIN`

## 📡 Endpoints disponibles

### 1. Liste des produits
```http
GET /api/products
```
**Accès** : Public  
**Description** : Récupère la liste de tous les produits

### 2. Détail d'un produit
```http
GET /api/products/{id}
```
**Accès** : Public  
**Description** : Récupère les détails d'un produit spécifique

### 3. Créer un produit
```http
POST /api/products
Content-Type: application/json

{
    "name": "Nom du produit",
    "description": "Description du produit",
    "price": 99.99,
    "stockQuantity": 10,
    "imageName": "image.jpg"
}
```
**Accès** : ROLE_ADMIN uniquement  
**Description** : Crée un nouveau produit

### 4. Modifier un produit
```http
PUT /api/products/{id}
Content-Type: application/json

{
    "name": "Nouveau nom",
    "price": 89.99,
    "stockQuantity": 15
}
```
**Accès** : ROLE_ADMIN uniquement  
**Description** : Modifie un produit existant

### 5. Supprimer un produit
```http
DELETE /api/products/{id}
```
**Accès** : ROLE_ADMIN uniquement  
**Description** : Supprime un produit

## 📊 Structure des données

### Produit (Product)
```json
{
    "id": 1,
    "name": "iPhone 15 Pro",
    "description": "Le dernier iPhone avec puce A17 Pro...",
    "price": 1199.99,
    "stockQuantity": 25,
    "imageName": "iphone15pro.jpg",
    "createdAt": "2024-01-15T10:30:00+00:00"
}
```

### Champs obligatoires
- `name` : Nom du produit (2-255 caractères)
- `price` : Prix (positif, max 999999.99)
- `stockQuantity` : Quantité en stock (≥ 0)

### Champs optionnels
- `description` : Description du produit
- `imageName` : Nom du fichier image

## 🔧 Utilisation avec cURL

### 1. Lister tous les produits
```bash
curl -X GET http://localhost:8000/api/products
```

### 2. Voir un produit spécifique
```bash
curl -X GET http://localhost:8000/api/products/1
```

### 3. Créer un produit (admin requis)
```bash
curl -X POST http://localhost:8000/api/products \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer VOTRE_TOKEN_JWT" \
  -d '{
    "name": "Nouveau Produit",
    "description": "Description du nouveau produit",
    "price": 149.99,
    "stockQuantity": 20,
    "imageName": "nouveau-produit.jpg"
  }'
```

### 4. Modifier un produit (admin requis)
```bash
curl -X PUT http://localhost:8000/api/products/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer VOTRE_TOKEN_JWT" \
  -d '{
    "name": "iPhone 15 Pro Max",
    "price": 1299.99,
    "stockQuantity": 30
  }'
```

### 5. Supprimer un produit (admin requis)
```bash
curl -X DELETE http://localhost:8000/api/products/1 \
  -H "Authorization: Bearer VOTRE_TOKEN_JWT"
```

## 🧪 Routes de test

### Test de l'API
```bash
curl -X GET http://localhost:8000/api/products/test
```

### Statistiques des produits
```bash
curl -X GET http://localhost:8000/api/products/stats
```

## 👤 Utilisateurs de test

### Admin
- **Email** : `admin@clickandship.com`
- **Mot de passe** : `admin123`
- **Rôles** : `ROLE_ADMIN`

### Utilisateur normal
- **Email** : `test@example.com`
- **Mot de passe** : `password123`
- **Rôles** : `ROLE_USER`

## 📚 Documentation interactive

Accédez à la documentation interactive d'API Platform :
- **URL** : http://localhost:8000/api
- **Fonctionnalités** :
  - Test des endpoints directement depuis le navigateur
  - Documentation automatique
  - Exemples de requêtes et réponses

## 🔍 Filtres et recherche

API Platform génère automatiquement des filtres pour :
- Recherche par nom
- Filtrage par prix
- Tri par date de création
- Pagination

### Exemples de filtres
```bash
# Rechercher par nom
GET /api/products?name=iPhone

# Filtrer par prix minimum
GET /api/products?price[gte]=100

# Trier par prix décroissant
GET /api/products?order[price]=desc

# Pagination
GET /api/products?page=1&limit=10
```

## 🚀 Démarrage rapide

1. **Démarrer le serveur** :
   ```bash
   php bin/console server:start
   ```

2. **Accéder à la documentation** :
   ```
   http://localhost:8000/api
   ```

3. **Tester les endpoints** :
   ```bash
   curl -X GET http://localhost:8000/api/products
   ```

## ⚠️ Notes importantes

- Les routes d'écriture nécessitent une authentification JWT avec le rôle `ROLE_ADMIN`
- Les validations sont automatiquement appliquées par Symfony
- Les erreurs retournent des codes HTTP appropriés avec des messages détaillés
- Tous les produits créés ont automatiquement un `createdAt` défini 
- se fichier et un fichier test pour faire se que je veut mais mais 