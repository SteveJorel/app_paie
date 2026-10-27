import 'package:flutter/material.dart';
import 'api_exception.dart';

class PagedResult {
  final List<Map<String, dynamic>> items;
  final int currentPage;
  final int lastPage;

  const PagedResult({required this.items, required this.currentPage, required this.lastPage});

  bool get hasMore => currentPage < lastPage;

  /// Lit la réponse d'un paginateur Laravel : { data: [...], current_page: 1, last_page: 3, ... }
  factory PagedResult.fromJson(dynamic json) {
    final map = json is Map ? json : const {};
    final data = map['data'] is List ? map['data'] as List : <dynamic>[];

    return PagedResult(
      items: data.map((e) => Map<String, dynamic>.from(e as Map)).toList(),
      currentPage: (map['current_page'] as num?)?.toInt() ?? 1,
      lastPage: (map['last_page'] as num?)?.toInt() ?? 1,
    );
  }
}

typedef PageLoader = Future<PagedResult> Function(int page);
typedef PagedItemBuilder = Widget Function(BuildContext context, Map<String, dynamic> item);

/// Liste paginée : chargement progressif, tirer-pour-actualiser, erreurs avec "Réessayer".
class PagedList extends StatefulWidget {
  final PageLoader loader;
  final PagedItemBuilder itemBuilder;
  final String emptyMessage;
  final String? errorHint;

  const PagedList({
    super.key,
    required this.loader,
    required this.itemBuilder,
    required this.emptyMessage,
    this.errorHint,
  });

  @override
  State<PagedList> createState() => _PagedListState();
}

class _PagedListState extends State<PagedList> with AutomaticKeepAliveClientMixin<PagedList> {
  final List<Map<String, dynamic>> _items = [];
  int _page = 0;
  bool _hasMore = true;
  bool _isLoading = false;
  String? _error;

  @override
  bool get wantKeepAlive => true;

  @override
  void initState() {
    super.initState();
    _loadNext();
  }

  Future<void> _refresh() async {
    if (_isLoading) return;

    _items.clear();
    _page = 0;
    _hasMore = true;
    _error = null;

    await _loadNext();
  }

  Future<void> _loadNext() async {
    if (_isLoading || !_hasMore) return;

    setState(() {
      _isLoading = true;
      _error = null;
    });

    try {
      final result = await widget.loader(_page + 1);
      if (!mounted) return;

      setState(() {
        _items.addAll(result.items);
        _page = result.currentPage;
        _hasMore = result.hasMore;
      });
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.message);
    } catch (_) {
      if (mounted) setState(() => _error = 'Connexion impossible. Vérifiez votre réseau.');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  Widget _errorBlock({required VoidCallback onRetry}) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Text(_error!, textAlign: TextAlign.center, style: TextStyle(color: Theme.of(context).colorScheme.error)),
        if (widget.errorHint != null) ...[
          const SizedBox(height: 8),
          Text(widget.errorHint!, textAlign: TextAlign.center, style: const TextStyle(color: Colors.grey)),
        ],
        const SizedBox(height: 12),
        OutlinedButton(onPressed: onRetry, child: const Text('Réessayer')),
      ],
    );
  }

  Widget _footer() {
    if (_isLoading) {
      return const Padding(
        padding: EdgeInsets.all(16),
        child: Center(child: SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2))),
      );
    }

    if (_error != null) {
      return Padding(padding: const EdgeInsets.all(16), child: _errorBlock(onRetry: _loadNext));
    }

    if (_hasMore) {
      return Center(child: TextButton(onPressed: _loadNext, child: const Text('Charger plus')));
    }

    return const SizedBox(height: 24);
  }

  @override
  Widget build(BuildContext context) {
    super.build(context);

    if (_items.isEmpty && _isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_items.isEmpty && _error != null) {
      return Center(child: Padding(padding: const EdgeInsets.all(24), child: _errorBlock(onRetry: _refresh)));
    }

    if (_items.isEmpty) {
      return RefreshIndicator(
        onRefresh: _refresh,
        child: ListView(
          children: [
            const SizedBox(height: 120),
            Center(child: Text(widget.emptyMessage)),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _refresh,
      child: ListView.separated(
        itemCount: _items.length + 1,
        separatorBuilder: (_, _) => const Divider(height: 1),
        itemBuilder: (context, index) {
          if (index < _items.length) return widget.itemBuilder(context, _items[index]);
          return _footer();
        },
      ),
    );
  }
}