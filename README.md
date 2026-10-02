# drupal-lab

Laboratoire Drupal 11 construit pour monter en compétence sur l'écosystème Drupal (venant de Laravel/Symfony). Le coeur du projet est un module custom, `event_api`, qui expose les événements à venir via une API JSON documentée.

## Contenu du dépôt

| Élément | Rôle |
|---|---|
| `web/modules/custom/event_api/` | Module custom : service, controller JSON, route |
| `web/modules/custom/event_api/openapi.yml` | Contrat de l'API (OpenAPI 3) |
| `config/sync/` | Configuration Drupal exportée (`drush cex`) : type de contenu « Événement » et ses champs |
| `composer.json` / `composer.lock` | Dépendances (Drupal core, Drush) |

Le coeur de Drupal (`web/core/`), `vendor/` et les fichiers générés ne sont pas versionnés : ils se recréent avec `composer install`.

## Prérequis

- PHP 8.3 ou supérieur, avec l'extension `pdo_sqlite`
- Composer

## Installation

```bash
git clone git@github.com:SylDav/drupal-lab.git
cd drupal-lab
composer install

vendor/bin/drush site:install standard \
  --db-url=sqlite://sites/default/files/.ht.sqlite \
  --account-name=admin --account-pass=admin \
  --site-name="Drupal Lab" -y
```

Dans `web/sites/default/settings.php`, définir le dossier de configuration :

```php
$settings['config_sync_directory'] = '../config/sync';
```

Activer le module et vider les caches :

```bash
vendor/bin/drush en event_api -y
vendor/bin/drush cr
```

Lancer le serveur local :

```bash
cd web && php -S localhost:8888 .ht.router.php
```

Le site est alors disponible sur `http://localhost:8888` (connexion : `/user/login`, identifiants de développement `admin` / `admin`, à ne jamais réutiliser ailleurs).

## Données de test

Le module lit les contenus de type `event`. Ce type et ses champs sont décrits dans `config/sync/` :

- `node.type.event.yml` : le type de contenu
- `field.*.field_date.yml` : champ **Date** (date seule)
- `field.*.field_lieu.yml` : champ **Lieu** (texte brut)

Créer quelques événements sur `/node/add/event`, dont un dans le passé pour vérifier le filtre.

## API

### `GET /api/events`

Retourne les événements publiés dont la date est aujourd'hui ou dans le futur, triés par date croissante.

| Paramètre | Type | Défaut | Description |
|---|---|---|---|
| `limit` | entier | 10 | Nombre maximum de résultats. Toute valeur hors de 1 à 50 est ramenée à la borne la plus proche. |

```bash
curl -s "http://localhost:8888/api/events?limit=5"
```

```json
{
  "data": [
    { "id": 3, "title": "Futur", "date": "2027-01-01", "location": "Rouen" }
  ]
}
```

L'accès demande la permission Drupal `access content`. Le contrat complet est dans [`openapi.yml`](web/modules/custom/event_api/openapi.yml) (visualisable sur [editor.swagger.io](https://editor.swagger.io)).

## Choix techniques

- **Service dédié** (`EventProvider`) : la logique de lecture est séparée du controller, donc réutilisable et testable. Les dépendances sont injectées, sans appel à `\Drupal::service()`.
- **Endpoint custom plutôt que JSON:API du core** : le core expose les entités brutes. Ici, le but est un contrat stable et minimal pour un consommateur externe, indépendant de la structure interne de Drupal.
- **Sécurité** : permission sur la route, `accessCheck(TRUE)` sur la requête d'entités, paramètre `limit` borné, aucune requête SQL construite à la main.

## Workflow de configuration

```bash
vendor/bin/drush cex -y   # exporter la config vers config/sync
vendor/bin/drush cim -y   # importer la config versionnée
```

Toute modification faite dans l'interface (champs, types de contenu, vues) se versionne en exportant puis en commitant les fichiers YAML.

## Suite prévue

- [ ] Test PHPUnit sur `EventProvider`
- [ ] Pipeline GitHub Actions (`composer install`, `phpcs --standard=Drupal`, PHPUnit)
- [ ] Mise en cache de la réponse JSON
- [ ] Exploration de l'intégration avec Moodle (web services REST)