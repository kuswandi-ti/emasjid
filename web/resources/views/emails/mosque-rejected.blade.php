<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pendaftaran Masjid – eMasjid</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; background-color: #f4f6f9; color: #333333; }
        .wrapper { max-width: 600px; margin: 32px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background-color: #dc3545; padding: 32px 40px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 26px; font-weight: 700; letter-spacing: 0.5px; }
        .header p { color: #f8d7da; font-size: 14px; margin-top: 6px; }
        .body { padding: 36px 40px; }
        .greeting { font-size: 16px; margin-bottom: 16px; }
        .intro { font-size: 15px; line-height: 1.7; margin-bottom: 24px; color: #555555; }
        .info-box { background-color: #f8f9fa; border-left: 4px solid #dc3545; border-radius: 4px; padding: 20px 24px; margin-bottom: 24px; }
        .info-box .info-row { display: flex; align-items: flex-start; margin-bottom: 12px; }
        .info-box .info-row:last-child { margin-bottom: 0; }
        .info-box .label { font-size: 13px; font-weight: 600; color: #6c757d; text-transform: uppercase; letter-spacing: 0.6px; min-width: 160px; padding-top: 2px; }
        .info-box .value { font-size: 15px; color: #212529; }
        .reason-box { background-color: #fff5f5; border: 1px solid #f5c6cb; border-radius: 6px; padding: 20px 24px; margin-bottom: 28px; }
        .reason-box .reason-label { font-size: 13px; font-weight: 600; color: #721c24; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 10px; }
        .reason-box .reason-text { font-size: 15px; color: #491217; line-height: 1.7; white-space: pre-line; }
        .reapply-box { background-color: #fff8e1; border-left: 4px solid #ffc107; border-radius: 4px; padding: 16px 20px; margin-bottom: 28px; }
        .reapply-box p { font-size: 14px; color: #856404; line-height: 1.7; }
        .cta { text-align: center; margin-bottom: 32px; }
        .cta a { display: inline-block; background-color: #6c757d; color: #ffffff; text-decoration: none; padding: 14px 36px; border-radius: 6px; font-size: 15px; font-weight: 600; }
        .cta a:hover { background-color: #545b62; }
        .divider { border: none; border-top: 1px solid #e9ecef; margin: 28px 0; }
        .footer-note { font-size: 13px; color: #868e96; line-height: 1.6; margin-bottom: 8px; }
        .footer { background-color: #f8f9fa; padding: 20px 40px; text-align: center; border-top: 1px solid #e9ecef; }
        .footer p { font-size: 12px; color: #adb5bd; line-height: 1.6; }
        .footer a { color: #dc3545; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        {{-- Header --}}
        <div class="header">
            <h1>🕌 eMasjid</h1>
            <p>Platform Pengelolaan Masjid Digital</p>
        </div>

        {{-- Body --}}
        <div class="body">
            <p class="greeting">Assalamu'alaikum Warahmatullahi Wabarakatuh,</p>

            <p class="intro">
                Terima kasih telah mendaftarkan masjid Anda di platform <strong>eMasjid</strong>.
                Setelah melalui proses tinjauan, kami perlu menginformasikan bahwa pendaftaran masjid
                Anda <strong>belum dapat disetujui</strong> pada saat ini.
            </p>

            {{-- Rejection Details --}}
            <div class="info-box">
                <div class="info-row">
                    <span class="label">Nama Masjid</span>
                    <span class="value">{{ $mosque->name }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Tanggal Ditinjau</span>
                    <span class="value">{{ $rejectionDate->translatedFormat('d F Y, H:i') }} WIB</span>
                </div>
                <div class="info-row">
                    <span class="label">Status</span>
                    <span class="value" style="color:#dc3545; font-weight:600;">✗ Ditolak</span>
                </div>
            </div>

            {{-- Rejection Reason --}}
            <div class="reason-box">
                <div class="reason-label">Alasan Penolakan</div>
                <div class="reason-text">{{ $rejectionReason }}</div>
            </div>

            {{-- Re-apply hint --}}
            <div class="reapply-box">
                <p>
                    <strong>Langkah selanjutnya:</strong> Harap perbaiki hal-hal yang disebutkan dalam
                    alasan penolakan di atas, kemudian ajukan kembali pendaftaran masjid Anda. Tim kami
                    akan dengan senang hati membantu proses verifikasi ulang.
                </p>
            </div>

            {{-- CTA – Contact Support --}}
            <div class="cta">
                <a href="mailto:support@emasjid.id">Hubungi Tim Dukungan</a>
            </div>

            <hr class="divider">

            <p class="footer-note">
                Jika Anda membutuhkan klarifikasi lebih lanjut mengenai keputusan ini atau ingin
                mendiskusikan langkah perbaikan, silakan hubungi kami melalui
                <a href="mailto:support@emasjid.id" style="color:#dc3545;">support@emasjid.id</a>.
            </p>
            <p class="footer-note">
                Email ini dikirim secara otomatis. Mohon jangan membalas email ini.
            </p>
        </div>

        {{-- Footer --}}
        <div class="footer">
            <p>
                &copy; {{ date('Y') }} <a href="{{ config('app.url') }}">eMasjid</a>. Semua hak dilindungi.<br>
                Anda menerima email ini karena telah mendaftarkan masjid di platform eMasjid.
            </p>
        </div>
    </div>
</body>
</html>
