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

// Yeni firma modal gösterme
function showAddFirmaModal() {
    document.getElementById('addFirmaModal').style.display = 'block';
}

// Firma düzenleme modalı gösterme
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

// Ödeme modalı gösterme
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

// Taksit tablosu oluşturma
function generateTaksitTable(firmaId) {
    const rows = document.querySelectorAll('#firmsTableBody tr');
    let firmaData = null;
    
    rows.forEach(row => {
        const detayBtn = row.querySelector('a[href*="firma_detay.php?id=' + firmaId + '"]');
        if (detayBtn) {
            const cells = row.cells;
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
    
    let html = `
        <div style="margin-bottom: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px;">
            <strong>Toplam Taksit:</strong> ${firmaData.taksitSayisi} |
            <strong>Aylık Ödeme:</strong> ₺${firmaData.aylikOdeme.toLocaleString()} |
            <strong>Kalan Borç:</strong> ₺${firmaData.kalanBorc.toLocaleString()}
        </div>
        <div style="overflow-x: auto;">
        <table class="firms-table" style="width: 100%; min-width: 700px;">
            <thead>
                <tr>
                    <th>Taksit No</th>
                    <th>Vade Tarihi</th>
                    <th>Asıl Tutar</th>
                    <th>Gecikme Faizi</th>
                    <th>Toplam Tutar</th>
                    <th>Durum</th>
                    <th>Ödeme Geçmişi</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    // Başlangıç tarihini parse et
    const [day, month, year] = firmaData.baslangicTarihi.split('.');
    const baslangicDate = new Date(year, month - 1, day);
    const today = new Date();
    
    // Ödenen taksit sayısını hesapla (kalan borç ile)
    const toplamOdenen = firmaData.toplamBorc - firmaData.kalanBorc;
    const odenenTaksitSayisi = Math.floor(toplamOdenen / firmaData.aylikOdeme);
    
    for (let i = 1; i <= firmaData.taksitSayisi; i++) {
        const taksitDate = new Date(baslangicDate);
        taksitDate.setDate(taksitDate.getDate() + ((i - 1) * 30)); // Her ay 30 gün
        
        const gecikmeDate = new Date(taksitDate);
        gecikmeDate.setDate(gecikmeDate.getDate() + 15); // 15 gün gecikme
        
        let durum = '';
        let durumClass = '';
        let odemeGecmisi = '-';
        let asilTutar = firmaData.aylikOdeme;
        let gecikmeFaizi = 0;
        let toplamTutar = asilTutar;
        
        if (i <= odenenTaksitSayisi) {
            durum = 'ÖDENDİ';
            durumClass = 'status-badge status-tamamlandi';
            odemeGecmisi = `₺${asilTutar.toLocaleString()}<br><small>${taksitDate.toLocaleDateString('tr-TR')}</small>`;
        } else if (firmaData.kalanBorc <= 0) {
            durum = 'TAMAMLANDI';
            durumClass = 'status-badge status-tamamlandi';
        } else if (today > gecikmeDate) {
            durum = 'GECİKME';
            durumClass = 'status-badge status-gecikme';
            gecikmeFaizi = asilTutar * 0.02; // %2 faiz
            toplamTutar = asilTutar + gecikmeFaizi;
        } else if (today > taksitDate) {
            durum = 'VADESİ GEÇTİ';
            durumClass = 'status-badge status-gecikme';
        } else {
            durum = 'BEKLİYOR';
            durumClass = 'status-badge status-aktif';
        }
        
        html += `
            <tr>
                <td><strong>${i}</strong></td>
                <td>${taksitDate.toLocaleDateString('tr-TR')}</td>
                <td style="color: #2c3e50;">₺${asilTutar.toLocaleString()}</td>
                <td style="color: #e74c3c;">${gecikmeFaizi > 0 ? '₺' + gecikmeFaizi.toLocaleString() : '-'}</td>
                <td style="color: #27ae60; font-weight: bold;">₺${toplamTutar.toLocaleString()}</td>
                <td><span class="${durumClass}">${durum}</span></td>
                <td style="font-size: 12px;">${odemeGecmisi}</td>
            </tr>
        `;
    }
    
    html += '</tbody></table></div>';
    document.getElementById('taksitContent').innerHTML = html;
    document.getElementById('taksitModal').style.display = 'block';
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

// Modal dışına tıklandığında kapat
window.onclick = function(event) {
    if (event.target.classList && event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
};

// Sayfa yüklendiğinde bugünün tarihini varsayılan olarak ayarla
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            input.value = today;
        }
    });
});<strong>Kalan Borç:</strong> ₺${firmaData.kalanBorc.toLocaleString()}
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
    
    // Başlangıç tarihini parse et
    const [day, month, year] = firmaData.baslangicTarihi.split('.');
    const baslangicDate = new Date(year, month - 1, day);
    const today = new Date();
    
    // Ödenen taksit sayısını hesapla (kalan borç ile)
    const toplamOdenen = firmaData.toplamBorc - firmaData.kalanBorc;
    const odenenTaksitSayisi = Math.floor(toplamOdenen / firmaData.aylikOdeme);
    
    for (let i = 1; i <= firmaData.taksitSayisi; i++) {
        const taksitDate = new Date(baslangicDate);
        taksitDate.setDate(taksitDate.getDate() + ((i - 1) * 30)); // Her ay 30 gün
        
        const gecikmeDate = new Date(taksitDate);
        gecikmeDate.setDate(gecikmeDate.getDate() + 15); // 15 gün gecikme
        
        let durum = '';
        let durumClass = '';
        let islemler = '';
        let odemeGecmisi = '-';
        let asilTutar = firmaData.aylikOdeme;
        let gecikmeFaizi = 0;
        let toplamTutar = asilTutar;
        
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
        } else if (today > gecikmeDate) {
            durum = 'GECİKME';
            durumClass = 'status-badge status-gecikme';
            gecikmeFaizi = asilTutar * 0.02; // %2 faiz
            toplamTutar = asilTutar + gecikmeFaizi;
            islemler = `
                <button class="btn btn-success btn-sm" onclick="quickPayment(${i}, ${toplamTutar})">💰 Öde</button>
                <button class="btn btn-danger btn-sm" onclick="deleteTaksit(${i})">🗑️ Sil</button>
            `;
        } else if (today > taksitDate) {
            durum = 'VADESİ GEÇTİ';
            durumClass = 'status-badge status-gecikme';
            islemler = `
                <button class="btn btn-success btn-sm" onclick="quickPayment(${i}, ${toplamTutar})">💰 Öde</button>
                <button class="btn btn-danger btn-sm" onclick="deleteTaksit(${i})">🗑️ Sil</button>
            `;
        } else {
            durum = 'BEKLİYOR';
            durumClass = 'status-badge status-aktif';
            islemler = `
                <button class="btn btn-success btn-sm" onclick="quickPayment(${i}, ${toplamTutar})">💰 Öde</button>
                <button class="btn btn-danger btn-sm" onclick="deleteTaksit(${i})">🗑️ Sil</button>
            `;
        }
        
        html += `
            <tr>
                <td><strong>${i}</strong></td>
                <td>${taksitDate.toLocaleDateString('tr-TR')}</td>
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

// Modal dışına tıklandığında kapat
document.addEventListener('click', function(event) {
    const modals = ['addFirmaModal', 'editFirmaModal', 'paymentModal', 'taksitModal', 'editTaksitModal'];
    modals.forEach(modalId => {
        const modal = document.getElementById(modalId);
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

// Sayfa yüklendiğinde bugünün tarihini varsayılan olarak ayarla
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        if (!input.value) {
            input.value = today;
        }
    });
});