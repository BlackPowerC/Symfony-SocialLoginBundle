# Comment utiliser ce bundle

Installation 
```bash
composer require fatigba/symfony-social-login-bundle
```

## Configuration des bundles
Dans votre fichier de configuration `config/bundles.php`, ajouter les entrées suivantes:

```php 
KnpU\OAuth2ClientBundle\KnpUOAuth2ClientBundle::class => ['all' => true]
```

```php
JFK\SocialLogin\SocialLoginBundle::class => ['all' => true]
```

## Configuration des fournisseurs OAuth2
Créer le fichier `config/packages/knpu_oauth2_client.yaml` s'il n'existe pas.  
Ajoutez y ensuite les lignes suivantes:  
```yaml
knpu_oauth2_client:
	clients:
```
### Le cas de Google
Pour configurer le client OAuht2 de Google, veuillez ajouter ce qui suit dans votre fichier de configuration,
```yaml
knpu_oauth2_client:
	clients:
		google:
			type: google
			client_id: xxxx
			client_secret: xxxx
			redirect_route: connect_google_check
```
Vous obtiendrez des valeurs pour client_id et client_secret en créant un `ID client OAuth`.  
[Console Google](https://console.developers.google.com) --> `API et Services` --> `Identifiants` --> `Créer des identifiants` --> `ID client OAuth`

## Injection de dépendances et découverte de services
Il s'agit de donner, impérativement un alias aux guards et de permettre l'injection de dépendances dans les controlleurs du bundles

`config/services.yaml`
```yaml
services:
	JFK\SocialLogin\Google\Controller\:
        resource: '../vendor/fatigba/symfony-social-login-bundle/src/SocialLogin/Google/Controller'
        tags: ['controller.service_arguments']
    .................
    .................
    .................
	jfk.sociallogin.google:
		class: JFK\SocialLogin\Google\Security\GoogleAuthenticator
```

Ensuite pour activer la détection des routes du controlleur, il faut activer la détection des routes dans `routes/annotations.yaml`:

```yaml
google_social_login_controllers:
    resource: ../../vendor/fatigba/symfony-social-login-bundle/src/SocialLogin/Google/Controller
    type: annotation
```

## Configuration de la sécurité
### Les guard
La recherche de l'utilisateur symfony se fait sur l'email en utilisant FOSUser.
Les classes de guard sont:
```php
JFk\SocialLogin\Google\Security\GoogleAuthenticator
```
En cas d'échec on est rediriger vers l'url `/login`

### Le fichier security.yaml
Dans le firewall adéquats, ajoutez les guards dont vous avez besoin:
```yaml
guard:
	authenticators:
		- jfk.sociallogin.google
```

Ajouter les controles d'accès suivants:
```yaml
access_control:
	- { path: ^/sociallogin/google, role: IS_AUTHENTICATED_ANONYMOUSLY }
```

## Les controlleurs de connexion 
### Le cas de Google (JFk\SocialLogin\Google\Controller)
Ce controlleur contient deux routes:

```php
@Route("/sociallogin/google", name="connect_google")
```
Cette route fait une redirection vers la page d'authentification de google.

```php
@Route("/sociallogin/google/check", name="connect_google_check")
```
C'est la route vers laquelle il faut faire la redirection après l'authentification de google.  
Elle se charge de connecter l'utilisateur symfony et de le rediriger vers la route `homepage`.