class ApiConfig {
  // 10.0.2.2 est l'alias standard de l'émulateur Android vers "localhost" de la machine hôte.
  // Pour un appareil physique sur le même réseau Wi-Fi, remplace par l'adresse IP locale de ta
  // machine (ex: 192.168.1.42) et démarre le serveur avec :
  //   php artisan serve --host=0.0.0.0 --port=8000
    static const String baseUrl = 'http://127.0.0.1:8000/api/v1';
}