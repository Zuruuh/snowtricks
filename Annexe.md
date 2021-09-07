# Parcours Développeur d’applications - Spécialisation PHP / Symfony
## Projet #6 - Développez de A à Z le site communautaire SnowTricks

### Annexe - Spécifications détaillées

Ce document présente les fonctionnalités présentes dans l’ensemble des pages qu’il vous est demandé de développer du projet #6 du parcours Développeur d’applications, spécialisation PHP/Symfony.

Page d’accueil - Liste des figures de snowboard
La page est accessible par tous les utilisateurs. On y verra la liste des noms de figures. L’utilisateur a la possibilité de cliquer sur le nom d’une figure pour accéder à la page de détails de cette figure.

Si l’utilisateur est connecté, il pourra cliquer sur :
une petit icône en forme de stylo situé juste à côté du nom qui redirigera l’utilisateur vers un formulaire de modification de figure ;
une corbeille située juste à côté du nom pour supprimer la figure.

Page de création de figure de snowboard -
Le formulaire comportera les champs suivants :
* nom ;
* description ;
* groupe de la figure ;
* une ou plusieurs illustration(s) ;
* une ou plusieurs vidéo(s).

Le formulaire n’est accessible que si l’utilisateur est authentifié.

Lorsque l’utilisateur soumet le formulaire, il faut que :
cette figure n’existe pas déjà en base de données (contrainte d’unicité sur le nom) ;
il soit redirigé sur la page du formulaire en cas d'erreur, en précisant le(s) type(s) d'erreurs ;
il soit redirigé sur la page listant des figures avec un message flash donnant une indication concernant le bon déroulement de l'enregistrement en base de données en cas de succès.

Pour les vidéos, l’utilisateur pourra coller une balise embed provenant de la plateforme de son choix (Youtube, Dailymotion…).
Page de modification de figure de snowboard
Les besoins sont les mêmes que pour la création. La seule différence est qu’il faut que les champs soient pré-remplis au moment où l’utilisateur arrive sur cette page.
Page de présentation d’une figure
Les informations suivantes doivent figurer sur la page :
* nom de la figure ;
* sa description ;
* le groupe de la figure ;
* la ou les photos rattachées à la figure ;
* la ou les vidéos rattachées à la figure ;
* l’espace de discussion (plus de détails à la section suivante).

La manière dont vous souhaitez disposer les informations est laissée à votre imagination. Le but étant que ce soit agréable et facile à consulter pour un utilisateur. Inspirez-vous de ce qui existe. 😉

Les URL des pages des figures doivent contenir le nom de la figure sous forme de slug.
Espace de discussion commun autour d’une figure
Les utilisateurs qui ne sont pas authentifiés peuvent consulter les discussions de toutes les figures. En revanche, ils ne peuvent pas poster de message.

Pour chaque message, il sera affiché les informations suivantes :
* le nom complet de l’auteur du message ;
* la photo de l’auteur du message ;
* la date de création du message ;
* le contenu du message.

Dans cet espace de discussion, on peut voir la liste des messages postés par les membres, du plus récent au plus ancien.
Ces messages doivent être paginés (10 par page).

Si l’utilisateur est authentifié, il peut voir un formulaire au-dessus de la liste avec un champs “message” qui est obligatoire. L’utilisateur peut poster autant de messages qu’il le souhaite.
Page de connexion
La connexion se fait sur une page dédiée via le nom d’utilisateur et le mot de passe.

Un bouton « mot de passe oublié » est présent et redirige l’utilisateur sur la page de mot de passe oublié.
Page d’inscription
La page d’inscription présente un formulaire qui demande :
* le nom d’utilisateur 
* l’adresse email 
* le mot de passe

Une fois ces informations entrées, l’utilisateur reçoit un email permettant de valider la création du compte et d’activer le compte (via un token de validation par exemple).

Page d’oubli du mot de passe
Lorsque l’utilisateur a oublié son mot de passe, il peut cliquer sur « mot de passe oublié » et sera redirigé vers la page d’oubli du mot de passe.

Sur celle-ci, il lui sera demandé son nom d’utilisateur via un formulaire. Une fois entré, il recevra un email avec un lien de création de nouveau mot de passe qui l’emmènera vers la page de réinitialisation du mot de passe. 
Page réinitialisation du mot de passe
Une fois arrivé sur cette page, l’utilisateur peut entrer un nouveau mot de passe via un formulaire.

Une fois son mot de passe changé, l’utilisateur sera redirigé vers la page d’accueil.