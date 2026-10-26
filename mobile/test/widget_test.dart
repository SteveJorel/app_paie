import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:mobile/main.dart';

void main() {
  testWidgets('App Paie démarre et affiche un indicateur de chargement', (WidgetTester tester) async {
    await tester.pumpWidget(const AppPaieApp());

    // Au lancement, le jeton n'est pas encore vérifié : un indicateur de chargement doit apparaître.
    expect(find.byType(CircularProgressIndicator), findsOneWidget);
  });
}