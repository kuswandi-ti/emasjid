# Flutter Fee Integration Guide

> Panduan integrasi fee calculation di Flutter mobile app.

---

## 📦 Models

### FeeSettings Model

```dart
class FeeSettings {
  final int feePercentage;
  final String feeMechanism;
  final bool feeActive;

  FeeSettings({
    required this.feePercentage,
    required this.feeMechanism,
    required this.feeActive,
  });

  factory FeeSettings.fromJson(Map<String, dynamic> json) {
    return FeeSettings(
      feePercentage: json['fee_percentage'] as int,
      feeMechanism: json['fee_mechanism'] as String,
      feeActive: json['fee_active'] as bool,
    );
  }

  double get feePercentageDecimal => feePercentage / 100;

  String get feeMechanismLabel {
    return feeMechanism == 'added_to_donor'
        ? 'Biaya ditanggung donatur'
        : 'Biaya dipotong dari donasi';
  }
}
```

### FeeCalculation Model

```dart
class FeeCalculation {
  final int amount;
  final int feeAmount;
  final int paymentAmount;
  final int mosqueReceives;
  final String feeMechanism;
  final int? paymentMethodFee;

  FeeCalculation({
    required this.amount,
    required this.feeAmount,
    required this.paymentAmount,
    required this.mosqueReceives,
    required this.feeMechanism,
    this.paymentMethodFee,
  });

  factory FeeCalculation.fromJson(Map<String, dynamic> json) {
    return FeeCalculation(
      amount: json['amount'] as int,
      feeAmount: json['platform_fee'] as int,
      paymentAmount: json['total_payment'] as int,
      mosqueReceives: json['mosque_receives'] as int,
      feeMechanism: json['fee_mechanism'] as String,
      paymentMethodFee: json['payment_method_fee'] as int?,
    );
  }

  int get totalFee => feeAmount + (paymentMethodFee ?? 0);

  String formatRupiah(int amount) {
    return 'Rp ${amount.toString().replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (Match m) => '${m[1]}.',
    )}';
  }

  String get amountFormatted => formatRupiah(amount);
  String get feeAmountFormatted => formatRupiah(feeAmount);
  String get paymentAmountFormatted => formatRupiah(paymentAmount);
  String get mosqueReceivesFormatted => formatRupiah(mosqueReceives);
  String get totalFeeFormatted => formatRupiah(totalFee);
}
```

---

## 🔧 Service/Repository

### Donation Repository

```dart
class DonationRepository {
  final ApiClient _apiClient;

  DonationRepository(this._apiClient);

  Future<FeeCalculation> calculateDonation({
    required int amount,
    required String paymentMethod,
  }) async {
    try {
      final response = await _apiClient.post(
        '/donations/calculate',
        data: {
          'amount': amount,
          'payment_method': paymentMethod,
        },
      );

      return FeeCalculation.fromJson(response.data['data']);
    } catch (e) {
      throw Exception('Failed to calculate donation: $e');
    }
  }

  Future<Donation> createDonation({
    required int mosqueId,
    required String category,
    required int amount,
    required String paymentMethod,
    bool isAnonymous = false,
  }) async {
    try {
      final response = await _apiClient.post(
        '/donations',
        data: {
          'mosque_id': mosqueId,
          'category': category,
          'amount': amount,
          'payment_method': paymentMethod,
          'is_anonymous': isAnonymous,
        },
      );

      return Donation.fromJson(response.data['data']);
    } catch (e) {
      throw Exception('Failed to create donation: $e');
    }
  }
}
```

---

## 🎨 UI Components

### Fee Breakdown Card

```dart
class FeeBreakdownCard extends StatelessWidget {
  final FeeCalculation calculation;

  const FeeBreakdownCard({
    Key? key,
    required this.calculation,
  }) : super(key: key);

  @override
  Widget build(BuildContext context) {
    final isAddedToDonor = calculation.feeMechanism == 'added_to_donor';

    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Rincian Donasi',
              style: Theme.of(context).textTheme.titleMedium?.copyWith(
                    fontWeight: FontWeight.bold,
                  ),
            ),
            const SizedBox(height: 16),
            
            // Amount
            _buildRow(
              'Nominal donasi',
              calculation.amountFormatted,
            ),
            
            if (isAddedToDonor) ...[
              const SizedBox(height: 8),
              _buildRow(
                'Biaya platform',
                calculation.feeAmountFormatted,
                isSubItem: true,
              ),
              if (calculation.paymentMethodFee != null) ...[
                const SizedBox(height: 8),
                _buildRow(
                  'Biaya payment',
                  calculation.formatRupiah(calculation.paymentMethodFee!),
                  isSubItem: true,
                ),
              ],
            ],
            
            const Divider(height: 24),
            
            // Total Payment
            _buildRow(
              'Total Pembayaran',
              calculation.paymentAmountFormatted,
              isTotal: true,
            ),
            
            const SizedBox(height: 16),
            
            // Info box
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.blue.shade50,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                children: [
                  Icon(
                    Icons.info_outline,
                    size: 20,
                    color: Colors.blue.shade700,
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'Masjid akan menerima ${calculation.mosqueReceivesFormatted}',
                      style: TextStyle(
                        fontSize: 13,
                        color: Colors.blue.shade700,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildRow(
    String label,
    String value, {
    bool isSubItem = false,
    bool isTotal = false,
  }) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(
          label,
          style: TextStyle(
            fontSize: isTotal ? 16 : 14,
            fontWeight: isTotal ? FontWeight.bold : FontWeight.normal,
            color: isSubItem ? Colors.grey[600] : null,
          ),
        ),
        Text(
          value,
          style: TextStyle(
            fontSize: isTotal ? 16 : 14,
            fontWeight: isTotal ? FontWeight.bold : FontWeight.w600,
            color: isTotal ? Colors.green : null,
          ),
        ),
      ],
    );
  }
}
```

---

## 📱 Donation Flow

### Step 1: Input Amount

```dart
class DonationAmountPage extends StatefulWidget {
  final Mosque mosque;

  const DonationAmountPage({Key? key, required this.mosque}) : super(key: key);

  @override
  State<DonationAmountPage> createState() => _DonationAmountPageState();
}

class _DonationAmountPageState extends State<DonationAmountPage> {
  final _amountController = TextEditingController();
  String _selectedCategory = 'infaq';
  
  final List<int> _quickAmounts = [
    10000,
    25000,
    50000,
    100000,
    250000,
    500000,
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Donasi'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Mosque info
            Card(
              child: ListTile(
                leading: CircleAvatar(
                  backgroundImage: widget.mosque.photoUrl != null
                      ? NetworkImage(widget.mosque.photoUrl!)
                      : null,
                  child: widget.mosque.photoUrl == null
                      ? const Icon(Icons.mosque)
                      : null,
                ),
                title: Text(widget.mosque.name),
                subtitle: Text(widget.mosque.city),
              ),
            ),
            
            const SizedBox(height: 24),
            
            // Category
            Text(
              'Kategori Donasi',
              style: Theme.of(context).textTheme.titleSmall,
            ),
            const SizedBox(height: 8),
            SegmentedButton<String>(
              segments: const [
                ButtonSegment(value: 'infaq', label: Text('Infaq')),
                ButtonSegment(value: 'zakat', label: Text('Zakat')),
                ButtonSegment(value: 'sadaqah', label: Text('Sedekah')),
                ButtonSegment(value: 'waqf', label: Text('Wakaf')),
              ],
              selected: {_selectedCategory},
              onSelectionChanged: (Set<String> selected) {
                setState(() {
                  _selectedCategory = selected.first;
                });
              },
            ),
            
            const SizedBox(height: 24),
            
            // Amount input
            Text(
              'Nominal Donasi',
              style: Theme.of(context).textTheme.titleSmall,
            ),
            const SizedBox(height: 8),
            TextField(
              controller: _amountController,
              keyboardType: TextInputType.number,
              decoration: const InputDecoration(
                prefixText: 'Rp ',
                hintText: '100.000',
                border: OutlineInputBorder(),
              ),
              inputFormatters: [
                FilteringTextInputFormatter.digitsOnly,
                TextInputFormatter.withFunction((oldValue, newValue) {
                  // Format with thousand separator
                  final text = newValue.text.replaceAll('.', '');
                  if (text.isEmpty) return newValue;
                  
                  final number = int.tryParse(text);
                  if (number == null) return oldValue;
                  
                  final formatted = number.toString().replaceAllMapped(
                    RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
                    (Match m) => '${m[1]}.',
                  );
                  
                  return TextEditingValue(
                    text: formatted,
                    selection: TextSelection.collapsed(offset: formatted.length),
                  );
                }),
              ],
            ),
            
            const SizedBox(height: 16),
            
            // Quick amount buttons
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: _quickAmounts.map((amount) {
                return OutlinedButton(
                  onPressed: () {
                    final formatted = amount.toString().replaceAllMapped(
                      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
                      (Match m) => '${m[1]}.',
                    );
                    _amountController.text = formatted;
                  },
                  child: Text('Rp ${amount ~/ 1000}rb'),
                );
              }).toList(),
            ),
            
            const SizedBox(height: 24),
            
            // Continue button
            ElevatedButton(
              onPressed: _amountController.text.isNotEmpty
                  ? _onContinue
                  : null,
              child: const Text('Lanjutkan'),
            ),
          ],
        ),
      ),
    );
  }

  void _onContinue() {
    final amount = int.tryParse(_amountController.text.replaceAll('.', ''));
    if (amount == null || amount <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nominal tidak valid')),
      );
      return;
    }

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => DonationPaymentMethodPage(
          mosque: widget.mosque,
          category: _selectedCategory,
          amount: amount,
        ),
      ),
    );
  }
}
```

### Step 2: Payment Method & Preview

```dart
class DonationPaymentMethodPage extends StatefulWidget {
  final Mosque mosque;
  final String category;
  final int amount;

  const DonationPaymentMethodPage({
    Key? key,
    required this.mosque,
    required this.category,
    required this.amount,
  }) : super(key: key);

  @override
  State<DonationPaymentMethodPage> createState() => _DonationPaymentMethodPageState();
}

class _DonationPaymentMethodPageState extends State<DonationPaymentMethodPage> {
  String? _selectedPaymentMethod;
  FeeCalculation? _calculation;
  bool _isCalculating = false;

  @override
  void initState() {
    super.initState();
    // Calculate default (QRIS)
    _calculateFee('QRIS');
  }

  Future<void> _calculateFee(String paymentMethod) async {
    setState(() {
      _isCalculating = true;
      _selectedPaymentMethod = paymentMethod;
    });

    try {
      final repository = context.read<DonationRepository>();
      final calculation = await repository.calculateDonation(
        amount: widget.amount,
        paymentMethod: paymentMethod,
      );

      setState(() {
        _calculation = calculation;
        _isCalculating = false;
      });
    } catch (e) {
      setState(() {
        _isCalculating = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Gagal menghitung: $e')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Metode Pembayaran'),
      ),
      body: Column(
        children: [
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Payment methods
                  Text(
                    'Pilih Metode Pembayaran',
                    style: Theme.of(context).textTheme.titleSmall,
                  ),
                  const SizedBox(height: 8),
                  
                  _buildPaymentMethodTile('QRIS', 'QRIS', Icons.qr_code),
                  _buildPaymentMethodTile('VA_BCA', 'BCA Virtual Account', Icons.account_balance),
                  _buildPaymentMethodTile('VA_MANDIRI', 'Mandiri Virtual Account', Icons.account_balance),
                  
                  const SizedBox(height: 24),
                  
                  // Fee calculation preview
                  if (_isCalculating)
                    const Center(child: CircularProgressIndicator())
                  else if (_calculation != null)
                    FeeBreakdownCard(calculation: _calculation!),
                ],
              ),
            ),
          ),
          
          // Bottom action
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.1),
                  blurRadius: 4,
                  offset: const Offset(0, -2),
                ),
              ],
            ),
            child: SafeArea(
              child: ElevatedButton(
                onPressed: _calculation != null ? _onConfirm : null,
                child: const Text('Bayar Sekarang'),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPaymentMethodTile(String code, String name, IconData icon) {
    final isSelected = _selectedPaymentMethod == code;
    
    return Card(
      color: isSelected ? Colors.blue.shade50 : null,
      child: ListTile(
        leading: Icon(icon),
        title: Text(name),
        trailing: isSelected
            ? const Icon(Icons.check_circle, color: Colors.blue)
            : null,
        onTap: () => _calculateFee(code),
      ),
    );
  }

  Future<void> _onConfirm() async {
    // Show loading
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (context) => const Center(child: CircularProgressIndicator()),
    );

    try {
      final repository = context.read<DonationRepository>();
      final donation = await repository.createDonation(
        mosqueId: widget.mosque.id,
        category: widget.category,
        amount: widget.amount,
        paymentMethod: _selectedPaymentMethod!,
      );

      // Close loading
      Navigator.pop(context);

      // Navigate to payment webview
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => DonationPaymentWebView(
            donation: donation,
          ),
        ),
      );
    } catch (e) {
      // Close loading
      Navigator.pop(context);
      
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Gagal membuat donasi: $e')),
      );
    }
  }
}
```

---

## 🧪 Testing

### Unit Test - Fee Calculation

```dart
void main() {
  group('FeeCalculation', () {
    test('formats rupiah correctly', () {
      final calc = FeeCalculation(
        amount: 100000,
        feeAmount: 2500,
        paymentAmount: 102500,
        mosqueReceives: 100000,
        feeMechanism: 'added_to_donor',
      );

      expect(calc.amountFormatted, 'Rp 100.000');
      expect(calc.feeAmountFormatted, 'Rp 2.500');
      expect(calc.paymentAmountFormatted, 'Rp 102.500');
    });

    test('calculates total fee correctly', () {
      final calc = FeeCalculation(
        amount: 100000,
        feeAmount: 2500,
        paymentAmount: 103200,
        mosqueReceives: 100000,
        feeMechanism: 'added_to_donor',
        paymentMethodFee: 700,
      );

      expect(calc.totalFee, 3200); // 2500 + 700
    });
  });
}
```

---

**Last Updated:** 8 Juni 2026
**Next:** Integrate saat Day 22-23 (API + Flutter Donation)
