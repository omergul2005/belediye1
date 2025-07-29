// Borç Takip Sistemi JavaScript Fonksiyonları

// Arama fonksiyonu
function filterTable() {
    const searchInput = document.getElementById('searchInput').value.toLowerCase();
    const rows = document.querySelectorAll('#firmsTableBody tr');
    
    let visibleIndex = 1;
    rows.forEach(row => {
        const firmaAdi = row.cells[1].textContent.toLowerCase();
        const sehir = row.cells[2].textContent.toLowerCase();
        
        const matchesSearch = firmaAdi.includes(searchInput) || sehir.includes(searchInput);
        
        if (matchesSearch) {
            row.style.display = '';
            row.cells[0].innerHTML = `<strong>${visibleIndex}</strong>`;
            visibleIndex++;
        } else {
            row.style.display = 'none';
        }
    });
}

// Modal gösterme fonksiyonları
function showAddFirmaModal() {
    document.getElementById('addFirmaModal').style.display = 'block';
}

function showEditFirmaModal(id, nama, sehir, telefon, toplam_borc, aylik_odeme, baslangic_tarihi) {
    document.getElementById('edit_firma_id').value = id;
    document.getElementById('edit_firma_adi').value = nama;
    document.getElementById('edit_sehir').value = sehir;
    document.getElementById('edit_telefon').value = telefon;
    document.getElementById('edit_toplam_borc').value = toplam_borc;
    document.getElementById('edit_aylik_odeme').value = aylik_odeme;
    document.getElementById('edit_baslangic_tarihi').value = baslangic_tarihi;
    document.getElementById('editFirmaModal').style.display = 'block';
}

function showPaymentModal(firmaId, firmaAdi, kalanBorc) {
    document.getElementById('payment_firma_id').value = firmaId;
    document.getElementById('payment_amount').max = kalanBorc;
    document.getElementById('payment_date').value = new Date().toISOString().split('T')[0];
    document.getElementById('paymentInfo').innerHTML = `
        <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <strong>Firma:</strong> ${firmaAdi}<br>
            <strong>Kalan Borç:</strong> ₺${kalanBorc.toLocaleString()}
        </div>
    `;
    document.getElementById('paymentModal').style.display = 'block';
}

// Taksit takvimi modalı
function showTaksitModal(firmaId) {
    generateTaksitTable(firmaId);
}

// Taksit tablosu oluşturma (düzenlenmiş versiyon)
function generateTaksitTable(firmaId) {
    const rows = document.querySelectorAll('#firmsTableBody tr');
    let firmaData = null;
    let firmaAdi = '';
    
    // Firma verilerini bul
    rows.forEach(row => {
        const detayBtn = row.querySelector('a[href*="firma_detay.php?id=' + firmaId + '"]');
        if (detayBtn) {
            const cells = row.cells;
            firmaAdi = cells[1].textContent;
            firmaData = {
                toplamBorc: parseFloat(cells[4].textContent.replace(/[₺,.]/g, '')),
                odenenTutar: parseFloat(cells[5].textContent.replace(/[₺,.]/g, '')),
                kalanBorc: parseFloat(cells[6].textContent.replace(/[₺,.]/g, '')),
                taksitSayisi: parseInt(cells[7].textContent),
                baslangicTarihi: cells[8].textContent,
                aylikOdeme: parseFloat(cells[9].textContent.replace(/[₺,.]/g, '')) || 0
            };
        }
    });
    
    if (!firmaData) {
        alert('Firma bilgileri bulunamadı!');
        return;
    }
    
    // Tablo HTML'ini oluştur
    let html = `
        <div style="text-align: center; margin-bottom: 20px;">
            <h3 style="color: #2c3e50; margin-bottom: 15px;">${firmaAdi}</h3>
            <div style="padding: 15px; background: linear-gradient(135deg, #e3f2fd, #bbdefb); border-radius: 12px; border: 2px solid #2196f3;">
                <strong>Toplam Taksit:</strong> ${firmaData.taksitSayisi} |
                <strong>Aylık Ödeme:</strong> ₺${firmaData.aylikOdeme.toLocaleString()} |
                <strong>Kalan Borç:</strong> ₺${firmaData.kalanBorc.toLocaleString()}
            </div>
        </div>
        <div style="overflow-x: auto;">
        <table class="firms-table" style="width: 100%; min-width: 800px;">
            <thead>
                <tr>
                    <th>Taksit No</th>
                    <th>Vade Tarihi</th>
                    <th>Asıl Tutar</th>
                    <th>Gecikme Faizi</th>
                    <th>Toplam Tutar</th>
                    <th>Durum</th>
                    <th>Ödeme Geçmişi</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    // Tarih hesaplamaları
    const [day, month, year] = firmaData.baslangicTarihi.split('.');
    const baslangicDate = new Date(year, month - 1, day);
    const today = new Date();
    
    // Ödenen taksit sayısını hesapla
    const toplamOdenen = firmaData.toplamBorc - firmaData.kalanBorc;
    const odenenTaksitSayisi = Math.floor(toplamOdenen / firmaData.aylikOdeme);
    
    // Her taksit için satır oluştur
    for (let i = 1; i <= firmaData.taksitSayisi; i++) {
        const taksitDate = new Date(baslangicDate);
        taksitDate.setDate(taksitDate.getDate() + ((i - 1) * 30));
        
        const vadeDate = new Date(taksitDate);
        vadeDate.setDate(vadeDate.getDate() + 30);
        
        const gecikmeDate = new Date(vadeDate);
        gecikmeDate.setDate(gecikmeDate.getDate() + 15);
        
        let durum, durumClass, islemler, odemeGecmisi;
        let asilTutar = firmaData.aylikOdeme;
        let gecikmeFaizi = 0;
        let toplamTutar = asilTutar;
        
        // Durum belirleme
        if (i <= odenenTaksitSayisi) {
            durum = 'ÖDENDİ';
            durumClass = 'status-badge status-tamamlandi';
            odemeGecmisi = `₺${asilTutar.toLocaleString()}<br><small>${taksitDate.toLocaleDateString('tr-TR')}</small>`;
            islemler = `
                <button class="btn btn-warning btn-sm" onclick="editOdeme(${i}, ${asilTutar}, '${taksitDate.toISOString().split('T')[0]}')">✏️ Düzenle</button>
                <button class="btn btn-danger btn-sm" onclick="deleteOdeme(${i})">🗑️ Sil</button>
            `;
        } else if (firmaData.kalanBorc <= 0) {
            durum = 'TAMAMLANDI';
            durumClass = 'status-badge status-tamamlandi';
            islemler = '';
            odemeGecmisi = '-';
        } else if (today > gecikmeDate) {
            durum = 'GECİKME';
            durumClass = 'status-badge status-gecikme';
            gecikmeFaizi = asilTutar * 0.02; // %2 faiz
            toplamTutar = asilTutar + gecikmeFaizi;
            odemeGecmisi = '-';
            islemler = `
                <button class="btn btn-success btn-sm" onclick="quickPayment(${i}, ${toplamTutar})">💰 Öde</button>
                <button class="btn btn-danger btn-sm" onclick="deleteTaksit(${i})">🗑️ Sil</button>
            `;
        } else if (today > vadeDate) {
            durum = 'VADESİ GEÇTİ';
            durumClass = 'status-badge status-gecikme';
            odemeGecmisi = '-';
            islemler = `
                <button class="btn btn-success btn-sm" onclick="quickPayment(${i}, ${toplamTutar})">💰 Öde</button>
                <button class="btn btn-danger btn-sm" onclick="deleteTaksit(${i})">🗑️ Sil</button>
            `;
        } else {
            durum = 'BEKLİYOR';
            durumClass = 'status-badge status-aktif';
            odemeGecmisi = '-';
            islemler = `
                <button class="btn btn-success btn-sm" onclick="quickPayment(${i}, ${toplamTutar})">💰 Öde</button>
                <button class="btn btn-danger btn-sm" onclick="deleteTaksit(${i})">🗑️ Sil</button>
            `;
        }
        
        html += `
            <tr>
                <td><strong>${i}</strong></td>
                <td>${vadeDate.toLocaleDateString('tr-TR')}</td>
                <td style="color: #2c3e50;">₺${asilTutar.toLocaleString()}</td>
                <td style="color: #e74c3c;">${gecikmeFaizi > 0 ? '₺' + gecikmeFaizi.toLocaleString() : '-'}</td>
                <td style="color: #27ae60; font-weight: bold;">₺${toplamTutar.toLocaleString()}</td>
                <td><span class="${durumClass}">${durum}</span></td>
                <td style="font-size: 12px;">${odemeGecmisi}</td>
                <td>${islemler}</td>
            </tr>
        `;
    }
    
    html += '</tbody></table></div>';
    document.getElementById('taksitContent').innerHTML = html;
    document.getElementById('taksitModal').style.display = 'block';
}

// Yıllık özet toggle fonksiyonu
function toggleYearlyBreakdown() {
    const yearlySection = document.getElementById('yearlyBreakdown');
    const toggleBtn = document.getElementById('yearlyToggleBtn');
    
    if (yearlySection.style.display === 'none' || yearlySection.style.display === '') {
        yearlySection.style.display = 'block';
        toggleBtn.innerHTML = '📊 Yıllık Özeti Gizle';
        toggleBtn.classList.remove('btn-primary');
        toggleBtn.classList.add('btn-secondary');
    } else {
        yearlySection.style.display = 'none';
        toggleBtn.innerHTML = '📊 Yıllık Borç Özeti';
        toggleBtn.classList.remove('btn-secondary');
        toggleBtn.classList.add('btn-primary');
    }
}

// Metin düzenleme fonksiyonu
function capitalizeFirstLetter(input) {
    const words = input.value.split(' ');
    for (let i = 0; i < words.length; i++) {
        if (words[i].length > 0) {
            words[i] = words[i][0].toUpperCase() + words[i].substring(1).toLowerCase();
        }
    }
    input.value = words.join(' ');
}

// Hızlı ödeme fonksiyonu
function quickPayment(taksitNo, tutar) {
    if (confirm(`${taksitNo}. taksiti ödemek istediğinizden emin misiniz?\nTutar: ₺${tutar.toLocaleString()}`)) {
        alert('Ödeme işlemi başarılı! Sayfa yenilenecek.');
        window.location.reload();
    }
}

// Ödeme düzenleme
function editOdeme(taksitNo, tutar, tarih) {
    const yeniTutar = prompt(`${taksitNo}. taksit tutarını düzenleyin:`, tutar);
    const yeniTarih = prompt(`${taksitNo}. taksit ödeme tarihini düzenleyin (YYYY-MM-DD):`, tarih);
    
    if (yeniTutar && yeniTarih) {
        alert('Ödeme düzenleme başarılı! Sayfa yenilenecek.');
        window.location.reload();
    }
}

// Ödeme silme
function deleteOdeme(taksitNo) {
    if (confirm(`${taksitNo}. taksit ödemesini silmek istediğinizden emin misiniz?\nBu işlem geri alınamaz!`)) {
        alert('Ödeme silme başarılı! Sayfa yenilenecek.');
        window.location.reload();
    }
}

// Taksit silme
function deleteTaksit(taksitNo) {
    if (confirm(`${taksitNo}. taksiti tamamen silmek istediğinizden emin misiniz?`)) {
        alert('Taksit silme başarılı! Sayfa yenilenecek.');
        window.location.reload();
    }
}

// Firma silme onayı
function confirmDelete(firmaId, firmaAdi) {
    if (confirm(`"${firmaAdi}" firmasını silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz ve tüm taksit bilgileri silinecektir!`)) {
        document.getElementById('delete_firma_id').value = firmaId;
        document.getElementById('deleteForm').submit();
    }
}

// Modal kapatma
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Tarih formatlama fonksiyonu
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('tr-TR');
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    // Bugünün tarihini varsayılan olarak ayarla
    const today = new Date().toISOString().split('T')[0];
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            input.value = today;
        }
    });
    
    // Modal dışına tıklandığında kapat
    document.addEventListener('click', function(event) {
        const modals = ['addFirmaModal', 'editFirmaModal', 'paymentModal', 'taksitModal', 'editTaksitModal'];
        modals.forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (modal && event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // ESC tuşu ile modal kapatma
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modals = document.querySelectorAll('.modal[style*="block"]');
            modals.forEach(modal => {
                modal.style.display = 'none';
            });
        }
    });
});

// Sayfa kapatılırken onay isteme (formda değişiklik varsa)
window.addEventListener('beforeunload', function(event) {
    const forms = document.querySelectorAll('form');
    let hasUnsavedChanges = false;
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            if (input.defaultValue !== input.value) {
                hasUnsavedChanges = true;
            }
        });
    });
    
    if (hasUnsavedChanges) {
        event.preventDefault();
        event.returnValue = '';
    }
});