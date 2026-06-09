<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dental Health Record</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #000; background: #fff; }
        .page { width: 210mm; margin: 0 auto; padding: 12mm 14mm; }

        /* ---- HEADER ---- */
        .header { display: flex; align-items: flex-start; gap: 8px; margin-bottom: 8px; }
        .logo { width: 58px; height: 58px; flex-shrink: 0; }
        .logo img { width: 58px; height: 58px; object-fit: contain; display: block; }
        .logo-fallback { width: 58px; height: 58px; border: 1px dashed #666; font-size: 9px; display: none; align-items: center; justify-content: center; text-align: center; line-height: 1.1; }
        .header-center { flex: 1; }
        .header-center .republic   { font-size: 11px; }
        .header-center .university { font-size: 12.5px; font-weight: bold; }
        .header-center .dept       { font-size: 11px; }
        .header-center .services   { font-size: 12px; font-weight: bold; }
        .form-box { border: 1px solid #000; width: 130px; min-height: 48px; padding: 3px 5px; font-size: 10px; flex-shrink: 0; }

        /* ---- TITLES ---- */
        .main-title    { text-align: center; font-size: 14px; font-weight: bold; letter-spacing: 0.5px; margin: 8px 0 4px; }
        .section-title { font-size: 12px; font-weight: bold; margin-bottom: 5px; }

        /* ---- PATIENT INFO ---- */
        .pi-name-row { display: flex; align-items: flex-end; margin-bottom: 1px; }
        .pi-name-row .lbl  { font-size: 12px; margin-right: 4px; white-space: nowrap; }
        .pi-name-row .line { flex: 1; border-bottom: 1px solid #000; height: 15px; }
        .pi-sub { display: flex; font-size: 10px; padding-left: 42px; margin-bottom: 4px; }
        .pi-sub span { flex: 1; }
        .pi-row { display: flex; align-items: center; gap: 3px; margin-bottom: 3px; font-size: 12px; }
        .pi-row .ibox { border: 1px solid #000; width: 26px; height: 15px; flex-shrink: 0; }
        .pi-row .line { border-bottom: 1px solid #000; height: 15px; }

        /* ---- ORAL EXAM ---- */
        .oral-title { font-size: 13px; font-weight: bold; margin: 10px 0 8px; }

        .cell { width: 28px; flex-shrink: 0; text-align: center; }
        .tooth-num { font-size: 9px; line-height: 1.4; }

        .sbox-wrap  { display: flex; flex-direction: column; }
        .sbox-row   { display: flex; }
        .sbox       { width: 28px; height: 14px; border: 1px solid #000; border-right: none; border-bottom: none; flex-shrink: 0; }
        .sbox-row:last-child .sbox { border-bottom: 1px solid #000; }
        .sbox:last-child            { border-right: 1px solid #000; }

        .gap-mid  { width: 14px; flex-shrink: 0; }
        .slabel   { width: 46px; font-size: 10px; font-weight: bold; line-height: 1.3; text-align: center; flex-shrink: 0; }
        .left-lbl { font-size: 10px; font-weight: bold; margin-left: auto; padding-left: 10px; align-self: center; }

        .flex-row   { display: flex; align-items: center; }
        .flex-row-c { display: flex; align-items: center; justify-content: center; }

        .primary-section { display: flex; flex-direction: column; align-items: center; }
        .primary-label-row { display: flex; align-items: center; width: 100%; }

        /* ---- DENTAL HISTORY (extended) ---- */
        .dh-line { display: flex; align-items: flex-end; margin-bottom: 5px; font-size: 11px; }
        .dh-line .fill { flex: 1; border-bottom: 1px solid #000; margin-left: 6px; height: 14px; }
        .dh-line .fill-mid { width: 80px; border-bottom: 1px solid #000; margin: 0 6px; height: 14px; }

        /* ---- MEDICAL HISTORY ---- */
        .med-section { margin-top: 14px; }
        .med-section .section-title { font-size: 13px; }
        .med-q { font-size: 11px; margin-bottom: 5px; display: flex; align-items: flex-end; flex-wrap: wrap; gap: 3px; }
        .med-q .fill { flex: 1; border-bottom: 1px solid #000; min-width: 60px; height: 14px; }
        .med-q .fill-sm { width: 90px; border-bottom: 1px solid #000; margin-left: 6px; height: 14px; }

        .checkbox-grid { display: flex; gap: 6px; margin-bottom: 4px; }
        .cb-box { width: 14px; height: 14px; border: 1px solid #000; flex-shrink: 0; display: inline-block; }
        .cb-label { font-size: 11px; }

        .conditions-row { display: flex; gap: 16px; margin: 6px 0; font-size: 11px; }
        .conditions-col { flex: 1; }
        .cond-item { display: flex; align-items: center; gap: 4px; margin-bottom: 3px; }
        .cond-item .blank { width: 36px; border-bottom: 1px solid #000; height: 12px; flex-shrink: 0; }

        /* ---- TREATMENT TABLE ---- */
        .tx-table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 11px; }
        .tx-table th { border: 1px solid #000; padding: 4px 6px; font-weight: bold; text-align: center; }
        .tx-table td { border: 1px solid #000; padding: 2px 4px; height: 20px; }
        .tx-table .col-date { width: 18%; }
        .tx-table .col-diag { width: 28%; }
        .tx-table .col-tx   { width: 28%; }
        .tx-table .col-att  { width: 26%; }

        /* ---- SIGNATURE / CONTACT ---- */
        .sig-row { display: flex; align-items: flex-end; gap: 8px; font-size: 11px; margin-bottom: 4px; }
        .sig-row .fill { flex: 1; border-bottom: 1px solid #000; height: 14px; }

        /* ---- PAGE BREAK ---- */
        .page-break { page-break-before: always; border-top: 2px dashed #ccc; margin: 20px 0; }

        @media print {
            body { margin: 0; }
            .page { padding: 10mm 12mm; }
            .page-break { border: none; margin: 0; }
        }
    </style>
</head>
<body>
<div class="page">

<!-- ====== HEADER ====== -->
<div class="header">
    <div class="logo">
        <img src="/images/PUP.png" alt="PUP Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
        <div class="logo-fallback">PUP<br>Logo</div>
    </div>
    <div class="header-center">
        <div class="republic">Republic of the Philippines</div>
        <div class="university">POLYTECHNIC UNIVERSITY OF THE PHILIPPINES</div>
        <div class="dept">Medical Services Department</div>
        <div class="services">DENTAL SERVICES</div>
    </div>
    <div class="form-box">
        PUP-DECH-6-MEDS-016<br>Rev.0<br>May 15, 2018
    </div>
</div>

<!-- ====== TITLES ====== -->
<div class="main-title">DENTAL HEALTH RECORD</div>
<div class="section-title">PATIENT INFORMATION RECORD</div>

<!-- ====== PATIENT INFO ====== -->
<div class="pi-name-row">
    <span class="lbl">Name:</span>
    <div class="line"></div>
</div>
<div class="pi-sub">
    <span>Last Name</span><span>First Name</span><span>Middle Name</span>
</div>
<div class="pi-row">
    <div class="ibox"></div>
    <span>Yr. and Section</span>
    <div class="line" style="width:80px;"></div>
    <div class="ibox"></div>
    <span>Faculty/College</span>
    <div class="line" style="width:60px;"></div>
    <div class="ibox"></div>
    <span>Admin/Dept.</span>
    <div class="line" style="flex:1;"></div>
</div>
<div class="pi-row">
    <span>Birthdate (mm/dd/yy)</span>
    <div class="line" style="width:90px;"></div>
    <span style="margin-left:8px;">Age:</span>
    <div class="line" style="width:45px;"></div>
    <span style="margin-left:8px;">Sex: M/F</span>
    <div class="line" style="flex:1;"></div>
</div>

<!-- ====== ORAL EXAMINATION ====== -->
<div class="oral-title">ORAL EXAMINATION</div>

<script>
function toothSvg(size) {
    size = size || 26;
    var cx = size / 2;
    var ro = cx - 1.5;
    var ri = cx * 0.32;
    return '<svg width="'+size+'" height="'+size+'" viewBox="0 0 '+size+' '+size+'" xmlns="http://www.w3.org/2000/svg" style="display:block;margin:0 auto;">'
        + '<circle cx="'+cx+'" cy="'+cx+'" r="'+ro+'" fill="none" stroke="#000" stroke-width="1.3"/>'
        + '<circle cx="'+cx+'" cy="'+cx+'" r="'+ri+'" fill="none" stroke="#000" stroke-width="1.2"/>'
        + '<line x1="'+(cx-ri*0.707)+'" y1="'+(cx-ri*0.707)+'" x2="'+(cx-ro*0.707)+'" y2="'+(cx-ro*0.707)+'" stroke="#000" stroke-width="1.2"/>'
        + '<line x1="'+(cx+ri*0.707)+'" y1="'+(cx-ri*0.707)+'" x2="'+(cx+ro*0.707)+'" y2="'+(cx-ro*0.707)+'" stroke="#000" stroke-width="1.2"/>'
        + '<line x1="'+(cx-ri*0.707)+'" y1="'+(cx+ri*0.707)+'" x2="'+(cx-ro*0.707)+'" y2="'+(cx+ro*0.707)+'" stroke="#000" stroke-width="1.2"/>'
        + '<line x1="'+(cx+ri*0.707)+'" y1="'+(cx+ri*0.707)+'" x2="'+(cx+ro*0.707)+'" y2="'+(cx+ro*0.707)+'" stroke="#000" stroke-width="1.2"/>'
        + '</svg>';
}
function statusStrip(teeth) {
    var top = '', bot = '';
    for (var i = 0; i < teeth.length; i++) { top += '<div class="sbox"></div>'; bot += '<div class="sbox"></div>'; }
    return '<div class="sbox-wrap"><div class="sbox-row">'+top+'</div><div class="sbox-row">'+bot+'</div></div>';
}
function numRow(teeth) {
    var out = '';
    for (var i = 0; i < teeth.length; i++) out += '<div class="cell"><div class="tooth-num">'+teeth[i]+'</div></div>';
    return out;
}
function teethRow(teeth, size) {
    size = size || 26;
    var out = '';
    for (var i = 0; i < teeth.length; i++) out += '<div class="cell">'+toothSvg(size)+'</div>';
    return out;
}
function render() {
    var html = '';
    html += '<div class="flex-row-c" style="margin-bottom:3px;"><div class="slabel">STATUS<br>RIGHT</div>'+statusStrip([55,54,53,52,51])+'<div class="gap-mid"></div>'+statusStrip([61,62,63,64,65])+'<div class="slabel">LEFT</div></div>';
    html += '<div class="flex-row-c" style="margin-bottom:1px;"><div class="slabel"></div><div style="display:flex;">'+numRow([55,54,53,52,51])+'</div><div class="gap-mid"></div><div style="display:flex;">'+numRow([61,62,63,64,65])+'</div><div class="slabel"></div></div>';
    html += '<div class="flex-row-c" style="margin-bottom:14px;"><div class="slabel"></div><div style="display:flex;">'+teethRow([55,54,53,52,51])+'</div><div class="gap-mid"></div><div style="display:flex;">'+teethRow([61,62,63,64,65])+'</div><div class="slabel"></div></div>';
    html += '<div class="flex-row-c" style="margin-bottom:3px;">'+statusStrip([18,17,16,15,14,13,12,11])+'<div class="gap-mid"></div>'+statusStrip([21,22,23,24,25,26,27,28])+'</div>';
    html += '<div class="flex-row-c" style="margin-bottom:1px;"><div style="display:flex;">'+numRow([18,17,16,15,14,13,12,11])+'</div><div class="gap-mid"></div><div style="display:flex;">'+numRow([21,22,23,24,25,26,27,28])+'</div></div>';
    html += '<div class="flex-row-c" style="margin-bottom:2px;"><div style="display:flex;">'+teethRow([18,17,16,15,14,13,12,11])+'</div><div class="gap-mid"></div><div style="display:flex;">'+teethRow([21,22,23,24,25,26,27,28])+'</div></div>';
    html += '<div class="flex-row-c" style="margin-bottom:1px;"><div style="display:flex;">'+teethRow([48,47,46,45,44,43,42,41])+'</div><div class="gap-mid"></div><div style="display:flex;">'+teethRow([31,32,33,34,35,36,37,38])+'</div></div>';
    html += '<div class="flex-row-c" style="margin-bottom:3px;"><div style="display:flex;">'+numRow([48,47,46,45,44,43,42,41])+'</div><div class="gap-mid"></div><div style="display:flex;">'+numRow([31,32,33,34,35,36,37,38])+'</div></div>';
    html += '<div class="flex-row-c" style="margin-bottom:14px;">'+statusStrip([48,47,46,45,44,43,42,41])+'<div class="gap-mid"></div>'+statusStrip([31,32,33,34,35,36,37,38])+'</div>';
    html += '<div class="flex-row-c" style="margin-bottom:1px;"><div class="slabel"></div><div style="display:flex;">'+teethRow([85,84,83,82,81])+'</div><div class="gap-mid"></div><div style="display:flex;">'+teethRow([71,72,73,74,75])+'</div><div class="slabel"></div></div>';
    html += '<div class="flex-row-c" style="margin-bottom:3px;"><div class="slabel"></div><div style="display:flex;">'+numRow([85,84,83,82,81])+'</div><div class="gap-mid"></div><div style="display:flex;">'+numRow([71,72,73,74,75])+'</div><div class="slabel"></div></div>';
    html += '<div class="flex-row-c" style="margin-bottom:2px;"><div class="slabel">STATUS<br>RIGHT</div>'+statusStrip([85,84,83,82,81])+'<div class="gap-mid"></div>'+statusStrip([71,72,73,74,75])+'<div class="slabel">LEFT</div></div>';
    document.getElementById('oral-chart').innerHTML = html;
}
document.addEventListener('DOMContentLoaded', render);
</script>

<div id="oral-chart"></div>

<!-- ====== LEGEND ====== -->
<div style="margin-top:18px; display:flex; justify-content:space-between;">
    <div style="flex:1;">
        <div style="font-size:11px; font-weight:bold; margin-bottom:3px;">Legend Condition</div>
        <div style="font-size:10px; line-height:1.7;">
            <b>D</b> - Decayed (Caries indicated for Filling)<br>
            <b>M</b> - Missing due to Caries<br>
            <b>F</b> - Filled<br>
            <b>I</b> - Caries Indicated for Extraction<br>
            <b>RF</b> - Root Fragment<br>
            <b>MO</b> - Missing due to Other Causes<br>
            <b>Im</b> - Impacted Tooth
        </div>
    </div>
    <div style="flex:1;">
        <div style="font-size:11px; font-weight:bold; margin-bottom:3px;">Restoration and Prosthetics</div>
        <div style="font-size:10px; line-height:1.7;">
            <b>J</b> - Jacket Crown<br>
            <b>A</b> - Amalgam Filling<br>
            <b>AB</b> - Abutment<br>
            <b>P</b> - Pontic<br>
            <b>In</b> - Inlay<br>
            <b>LC</b> - Light Cure Composite<br>
            <b>Rm</b> - Removable Denture
        </div>
    </div>
    <div style="flex:1;">
        <div style="font-size:11px; font-weight:bold; margin-bottom:3px;">Surgery</div>
        <div style="font-size:10px; line-height:1.7;">
            <b>X</b> - Extraction due to Caries<br>
            <b>XO</b> - Extraction due to Other Causes<br>
            <b>&#x221A;</b> - Present Teeth<br>
            <b>Cm</b> - Congenitally Missing<br>
            <b>Sp</b> - Supernumerary
        </div>
    </div>
</div>

<!-- ====== DENTAL HISTORY ====== -->
<div style="margin-top:18px;">
    <div style="font-size:13px; font-weight:bold; margin-bottom:6px;">DENTAL HISTORY</div>
    <div style="margin-bottom:6px;">
        <span style="font-weight:700;">Previous Dentist:</span>
        <span style="display:inline-block; width:60%; border-bottom:1px solid #000; margin-left:8px;">&nbsp;</span>
    </div>
    <div style="margin-bottom:10px;">
        <span style="font-weight:700;">Last Dental Visit:</span>
        <span style="display:inline-block; width:60%; border-bottom:1px solid #000; margin-left:8px;">&nbsp;</span>
    </div>

    <div style="font-weight:700; margin-bottom:6px;">Pls indicate <span style="text-decoration:underline;">YES</span> or <span style="text-decoration:underline;">NO</span> to the following:</div>

    <div style="font-size:11px; line-height:1.6;">
        <div style="margin-bottom:6px;">Do your gums bleed while brushing/ flossing? <span style="display:inline-block; width:55%; border-bottom:1px solid #000; margin-left:8px;">&nbsp;</span></div>
        <div style="margin-bottom:6px;">Are your teeth sensitive to hot or cold? <span style="display:inline-block; width:55%; border-bottom:1px solid #000; margin-left:8px;">&nbsp;</span></div>
        <div style="margin-bottom:6px;">Are your teeth sensitive to sweets or sour? <span style="display:inline-block; width:55%; border-bottom:1px solid #000; margin-left:8px;">&nbsp;</span></div>
        <div style="margin-bottom:6px;">Do you feel any pain in your teeth? <span style="display:inline-block; width:55%; border-bottom:1px solid #000; margin-left:8px;">&nbsp;</span></div>
        <div style="margin-bottom:6px;">Do you have any sores/ lumps in or near your mouth? <span style="display:inline-block; width:48%; border-bottom:1px solid #000; margin-left:8px;">&nbsp;</span></div>
        <div style="margin-bottom:6px;">Have you had any head, neck, or jaw injuries? <span style="display:inline-block; width:48%; border-bottom:1px solid #000; margin-left:8px;">&nbsp;</span></div>
        <div style="margin-bottom:6px;">Have you ever experienced any of the following? <span style="display:inline-block; width:48%; border-bottom:1px solid #000; margin-left:8px;">&nbsp;</span></div>
        <div style="margin-bottom:6px;">Clicking: <span style="display:inline-block; width:40%; border-bottom:1px solid #000; margin-left:8px;">&nbsp;</span></div>
        <div style="margin-bottom:6px;">Pain (joint, side of the face): <span style="display:inline-block; width:40%; border-bottom:1px solid #000; margin-left:8px;">&nbsp;</span></div>
        <div style="margin-bottom:6px;">Difficulty in opening / closing: <span style="display:inline-block; width:40%; border-bottom:1px solid #000; margin-left:8px;">&nbsp;</span></div>
        <div style="margin-bottom:6px;">Difficulties in chewing: <span style="display:inline-block; width:40%; border-bottom:1px solid #000; margin-left:8px;">&nbsp;</span></div>
        <div style="margin-bottom:6px;">Frequent headaches: <span style="display:inline-block; width:40%; border-bottom:1px solid #000; margin-left:8px;">&nbsp;</span></div>
        <div style="margin-bottom:6px;">Do you clench or grind your teeth: <span style="display:inline-block; width:40%; border-bottom:1px solid #000; margin-left:8px;">&nbsp;</span></div>
        <div style="margin-bottom:6px;">Frequent lips/ cheek biting: <span style="display:inline-block; width:40%; border-bottom:1px solid #000; margin-left:8px;">&nbsp;</span></div>
        <div style="margin-bottom:6px;">Have you notice loosening of your teeth? <span style="display:inline-block; width:40%; border-bottom:1px solid #000; margin-left:8px;">&nbsp;</span></div>
        <div style="margin-bottom:6px;">Does food get caught between your teeth? <span style="display:inline-block; width:40%; border-bottom:1px solid #000; margin-left:8px;">&nbsp;</span></div>
        <div style="margin-bottom:6px;">Have you ever had reaction to any kind of medicine or dental anesthetic? If yes, please provide details.<br>
            <span style="display:inline-block; width:95%; border-bottom:1px solid #000;">&nbsp;</span>
        </div>
    </div>

    <!-- ====== DENTAL HISTORY CONTINUED (from page 2) ====== -->
    <div style="font-size:11px; line-height:1.6; margin-top:4px;">
        <div class="dh-line">Have you had any periodontal (gum) treatment?<span class="fill"></span></div>
        <div class="dh-line">Have you had difficult extraction before?<span class="fill-mid"></span>When?<span class="fill"></span></div>
        <div class="dh-line">Have you had prolonged bleeding following tooth extractions before?<span class="fill"></span></div>
        <div class="dh-line">Do you wear complete or partial dentures?<span class="fill-mid"></span>If yes, date of placement<span class="fill"></span></div>
        <div class="dh-line">Have you had orthodontic treatment?<span class="fill-mid"></span>if yes, date of completion<span class="fill"></span></div>
        <div class="dh-line">Additional concerns<span class="fill"></span></div>
        <div style="border-bottom:1px solid #000; margin-bottom:4px;">&nbsp;</div>
    </div>
</div>

<!-- ====== PAGE BREAK ====== -->
<div class="page-break"></div>

<!-- ====== MEDICAL HISTORY ====== -->
<div class="med-section">
    <div class="section-title" style="font-size:13px; margin-bottom:8px;">MEDICAL HISTORY</div>

    <div class="med-q">1.&nbsp; Are you in good health?&nbsp; <span style="border-bottom:1px solid #000; width:40px; display:inline-block;">&nbsp;</span>&nbsp;Yes&nbsp; <span style="border-bottom:1px solid #000; width:40px; display:inline-block;">&nbsp;</span>&nbsp;No, if no, pls provide details <span class="fill"></span></div>
    <div class="med-q">2.&nbsp; When was the last time you had medical examination? <span class="fill"></span></div>
    <div class="med-q">3.&nbsp; Are you presently receiving treatment for any illness? If yes, pls provide details.</div>
    <div style="border-bottom:1px solid #000; margin-bottom:6px; height:14px;">&nbsp;</div>
    <div class="med-q">4.&nbsp;&nbsp; Have you ever been hospitalized? <span class="fill-sm"></span> If yes, pls provide details.</div>
    <div style="border-bottom:1px solid #000; margin-bottom:6px; height:14px;">&nbsp;</div>
    <div class="med-q">5.&nbsp; Are you allergic to any of the following?</div>
    <div style="font-size:11px; margin-bottom:5px;">
        &nbsp;&nbsp;&nbsp;&nbsp;Medicines <span style="display:inline-block; width:100px; border-bottom:1px solid #000;">&nbsp;</span>
        &nbsp;Foods <span style="display:inline-block; width:120px; border-bottom:1px solid #000;">&nbsp;</span>
        &nbsp;others <span style="display:inline-block; flex:1; width:80px; border-bottom:1px solid #000;">&nbsp;</span>
    </div>
    <div class="med-q">6.&nbsp; Are you taking any prescription/non-prescription medication? <span style="border-bottom:1px solid #000; width:40px; display:inline-block;">&nbsp;</span></div>
    <div class="med-q">&nbsp;&nbsp;&nbsp;&nbsp;If so, please specify? <span class="fill"></span></div>
    <div style="font-size:11px; margin-bottom:6px;">
        7.&nbsp; For women only:&nbsp; Are you pregnant?
        <span style="margin-left:8px; border:1px solid #000; display:inline-block; width:14px; height:14px;">&nbsp;</span>&nbsp;Yes
        <span style="margin-left:6px; border:1px solid #000; display:inline-block; width:14px; height:14px;">&nbsp;</span>&nbsp;No
    </div>
    <div style="font-size:11px; margin-bottom:6px;">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Are you nursing?
        <span style="margin-left:8px; border:1px solid #000; display:inline-block; width:14px; height:14px;">&nbsp;</span>&nbsp;Yes
        <span style="margin-left:6px; border:1px solid #000; display:inline-block; width:14px; height:14px;">&nbsp;</span>&nbsp;No
    </div>
    <div style="font-size:11px; margin-bottom:8px;">
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Are you taking birth control pills?
        <span style="margin-left:8px; border:1px solid #000; display:inline-block; width:14px; height:14px;">&nbsp;</span>&nbsp;Yes
        <span style="margin-left:6px; border:1px solid #000; display:inline-block; width:14px; height:14px;">&nbsp;</span>&nbsp;No
    </div>

    <div style="font-size:11px; margin-bottom:6px;">8.&nbsp; Pls indicate below if <b>presently have</b> or <b>have ever had</b> any of the following:</div>
    <div class="conditions-row">
        <div class="conditions-col">
            <div class="cond-item"><span class="blank"></span>AID/ HIV</div>
            <div class="cond-item"><span class="blank"></span>ALCOHOL OR CHEMICAL DEPENDENCY</div>
            <div class="cond-item"><span class="blank"></span>ARTHRITIS/ RHEUMATISM</div>
            <div class="cond-item"><span class="blank"></span>ARTIFICIAL JOINTS OR VALVES</div>
            <div class="cond-item"><span class="blank"></span>ASTHMA</div>
            <div class="cond-item"><span class="blank"></span>BLOOD TRANSFUSION</div>
            <div class="cond-item"><span class="blank"></span>CANCER/ RADIOTHERAPHY/ CHEMOTHERAPHY</div>
            <div class="cond-item"><span class="blank"></span>DIABETES</div>
            <div class="cond-item"><span class="blank"></span>EATING DISORDERS</div>
            <div class="cond-item"><span class="blank"></span>EPILEPSY/ SEIZURES</div>
        </div>
        <div class="conditions-col">
            <div class="cond-item"><span class="blank"></span>FAINTING/ DIZZY SPELLS</div>
            <div class="cond-item"><span class="blank"></span>HIGH/ LOW BLOOD PRESSURE</div>
            <div class="cond-item"><span class="blank"></span>HYPER/HYPO GLYCEMIA</div>
            <div class="cond-item"><span class="blank"></span>KIDNEY DISEASE</div>
            <div class="cond-item"><span class="blank"></span>LIVER DISEASE (HEPATITIS/JAUNDICE)</div>
            <div class="cond-item"><span class="blank"></span>MENTAL/ NERVOUS DISORDER</div>
            <div class="cond-item"><span class="blank"></span>STOMACH ULCERS</div>
            <div class="cond-item"><span class="blank"></span>STROKE</div>
            <div class="cond-item"><span class="blank"></span>TUBERCULOSIS</div>
            <div class="cond-item"><span class="blank"></span>VENEREAL/ COMMUNICABLE DISEASE</div>
        </div>
    </div>

    <div class="med-q" style="margin-top:6px;">9.&nbsp; Do use tobacco products or any derivatives? <span class="fill-sm"></span> If yes, how much per day? <span class="fill-sm"></span> per week? <span class="fill-sm"></span></div>
    <div class="med-q">10.&nbsp;&nbsp; Do you suffer from headaches <span class="fill-sm"></span> earaches <span class="fill-sm"></span> or neck aches <span class="fill-sm"></span></div>
    <div class="med-q">11.&nbsp; Is there any additional information related to your health that has not been addressed above?</div>
    <div style="border-bottom:1px solid #000; margin-bottom:4px; height:14px;">&nbsp;</div>
    <div style="border-bottom:1px solid #000; margin-bottom:12px; height:14px;">&nbsp;</div>

    <!-- Signature / Emergency Contact -->
    <div class="sig-row">
        <span>Person to contact in case of emergency:</span>
        <span class="fill"></span>
        <span style="margin-left:10px;">Relation to patient</span>
        <span class="fill"></span>
    </div>
    <div class="sig-row">
        <span>Contact Number:</span>
        <span style="width:130px; border-bottom:1px solid #000; margin-left:8px; height:14px;">&nbsp;</span>
    </div>
    <div class="sig-row" style="margin-top:4px;">
        <span>Patient's Signature:</span>
        <span class="fill"></span>
        <span style="margin-left:10px;">Contact Number:</span>
        <span class="fill"></span>
    </div>
    <div style="font-size:11px; font-style:italic; margin-bottom:12px;">(All information will be treated with confidentiality)</div>
</div>

<!-- ====== TREATMENT RECORD TABLE ====== -->
<table class="tx-table">
    <thead>
        <tr>
            <th class="col-date">Date</th>
            <th class="col-diag">Diagnosis</th>
            <th class="col-tx">Treatment</th>
            <th class="col-att">Attending Dentist/Dental Aide</th>
        </tr>
    </thead>
    <tbody>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
    </tbody>
</table>

</div><!-- .page -->
</body>
</html>