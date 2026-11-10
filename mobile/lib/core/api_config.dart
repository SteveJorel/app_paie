class ApiConfig {
  // Adresse de l'API, modifiable au lancement sans toucher au code :
  //   flutter run --dart-define=API_BASE_URL=http://192.168.1.42:8000/api/v1
  // Par défaut : 10.0.2.2 = « localhost » de ton PC vu depuis l'émulateur Android.
  // Appareil physique : utiliser l'IP locale du PC et lancer le serveur avec
  //   php artisan serve --host=0.0.0.0 --port=8000
  static const String baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://10.0.2.2:8000/api/v1',
  );
}