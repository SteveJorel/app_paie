/// Affiche une date du serveur en heure locale : "26/10/2026 14:05".
/// Les dates du serveur sont en UTC ; une valeur sans fuseau ("2026-10-26 11:00:00") est lue comme UTC.
String formatDateTime(dynamic value) {
  if (value == null) return '-';

  final raw = value.toString();
  final normalized = (raw.contains('T') || raw.endsWith('Z')) ? raw : '${raw.replaceFirst(' ', 'T')}Z';
  final parsed = DateTime.tryParse(normalized);

  if (parsed == null) return raw;

  final local = parsed.toLocal();
  String two(int n) => n.toString().padLeft(2, '0');

  return '${two(local.day)}/${two(local.month)}/${local.year} ${two(local.hour)}:${two(local.minute)}';
}