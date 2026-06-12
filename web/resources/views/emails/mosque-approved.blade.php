<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masjid Anda Telah Disetujui – eMasjid</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; background-color: #f4f6f9; color: #333333; }
        .wrapper { max-width: 600px; margin: 32px auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .header { background-color: #28a745; padding: 32px 40px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 26px; font-weight: 700; letter-spacing: 0.5px; }
        .header p { color: #d4edda; font-size: 14px; margin-top: 6px; }
        .body { padding: 36px 40px; }
        .greeting { font-size: 16px; margin-bottom: 16px; }
        .intro { font-size: 15px; line-height: 1.7; margin-bottom: 24px; color: #555555; }
        .info-box { background-color: #f8f9fa; border-left: 4px solid #28a745; border-radius: 4px; padding: 20px 24px; margin-bottom: 28px; }
        .info-box .info-row { display: flex; align-items: flex-start; margin-bottom: 12px; }
        .info-box .info-row:last-child { margin-bottom: 0; }
        .info-box .label { font-size: 13px; font-weight: 600; color: #6c757d; text-transform: uppercase; letter-spacing: 0.6px; min-width: 160px; padding-top: 2px; }
        .info-box .value { font-size: 15px; color: #212529; }
        .invitation-code-block { background-color: #e9f7ef; border: 2px dashed #28a745; border-radius: 6px; text-align: center; padding: 20px; margin-bottom: 28px; }
        .invitation-code-block .code-label { font-size: 13px; color: #6c757d; font-weight: 600; text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 8px; }
        .invitation-code-block .code { font-size: 32px; font-weight: 700; letter-spacing: 6px; color: #155724; font-family: 'Courier New', Courier, monospace; }
        .invitation-code-block .code-hint { font-size: 13px; color: #6c757d; margin-top: 8px; }
        .cta { text-align: center; margin-bottom: 32px; }
        .cta a { display: inline-block; background-color: #28a745; color: #ffffff; text-decoration: none; padding: 14px 36px; border-radius: 6px; font-size: 15px; font-weight: 600; }
        .cta a:hover { background-color: #218838; }
        .divider { border: none; border-top: 1px solid #e9ecef; margin: 28px 0; }
        .footer-note { font-size: 13px; color: #868e96; line-height: 1.6; margin-bottom: 8px; }
        .footer { background-color: #f8f9fa; padding: 20px 40px; text-align: center; border-top: 1px solid #e9ecef; }
        .footer p { font-size: 12px; color: #adb5bd; line-height: 1.6; }
        .footer a { color: #28a745; text-decoration: none; }
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
                Alhamdulillah, kami dengan senang hati menginformasikan bahwa pendaftaran masjid Anda
                di platform <strong>eMasjid</strong> telah <strong>disetujui</strong>. Masjid Anda
                kini aktif dan dapat mulai menggunakan seluruh fitur yang tersedia.
            </p>

            {{-- Approval Details --}}
            <div class="info-box">
                <div class="info-row">
                    <span class="label">Nama Masjid</span>
                    <span class="value">{{ $mosque->name }}</span>
                </div>
                <div class="info-row">
                    <span class="label">Tanggal Disetujui</span>
                    <span class="value">{{ $approvalDate->translatedFormat('d F Y, H:i') }} WIB</span>
                </div>
                <div class="info-row">
                    <span class="label">Status</span>
                    <span class="value" style="color:#28a745; font-weight:600;">✓ Aktif</span>
                </div>
            </div>

            {{-- Invitation Code --}}
            <div class="invitation-code-block">
                <div class="code-label">Kode Undangan Jamaah</div>
                <div class="code">{{ $invitationCode }}</div>
                <div class="code-hint">Bagikan kode ini kepada jamaah agar dapat bergabung ke masjid Anda.</div>
            </div>

            {{-- CTA --}}
            <div class="cta">
                <a href="{{ route('admin.dashboard') }}">Masuk ke Panel Admin</a>
            </div>

            <hr class="divider">

            <p class="footer-note">
                Jika Anda mengalami kendala dalam mengakses panel admin atau memiliki pertanyaan,
                silakan hubungi tim dukungan kami melalui
                <a href="mailto:support@emasjid.id" style="color:#28a745;">support@emasjid.id</a>.
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
