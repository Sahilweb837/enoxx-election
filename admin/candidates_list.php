<?php
require_once 'admin/config.php';
requireLogin();

$userId = $_SESSION['user_id'];

// Get all candidates created by this employee
$stmt = $pdo->prepare("
    SELECT c.*, d.district_name, b.block_name, p.panchayat_name,
    CASE 
        WHEN c.jila_parishad_pradhan = 'jila_parishad' THEN 'जिला परिषद'
        WHEN c.jila_parishad_pradhan = 'pradhan' THEN 'प्रधान'
        ELSE 'N/A'
    END as jila_parishad_pradhan_text
    FROM candidates c
    LEFT JOIN districts d ON c.district_id = d.id
    LEFT JOIN blocks b ON c.block_id = b.id
    LEFT JOIN panchayats p ON c.panchayat_id = p.id
    WHERE c.created_by = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$userId]);
$candidates = $stmt->fetchAll();

include 'admin/includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <h1><i class="fas fa-list"></i> My Candidates</h1>
        <p>View all candidates you have registered</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="window.location.href='index.php'">
            <i class="fas fa-plus"></i> Add New
        </button>
    </div>
</div>

<div class="candidates-table">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                 <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Jila Parishad/Pradhan</th>
                    <th>Panchayat</th>
                    <th>Mobile</th>
                    <th>Verified</th>
                    <th>Date</th>
                    <th>Actions</th>
                 </tr>
            </thead>
            <tbody>
                <?php if (empty($candidates)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 50px;">
                            <i class="fas fa-inbox" style="font-size: 3em; color: #94a3b8; margin-bottom: 10px; display: block;"></i>
                            <p>No candidates found. Click "Add New" to register your first candidate!</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($candidates as $candidate): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($candidate['candidate_id'] ?? 'N/A'); ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($candidate['candidate_name_en'] ?? 'N/A'); ?>
                            <?php if ($candidate['whatsapp_verified']): ?>
                                <span class="blue-tick"><i class="fas fa-check-circle" style="color: #1da1f2;"></i></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($candidate['jila_parishad_pradhan_text'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($candidate['panchayat_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($candidate['mobile_number'] ?? 'N/A'); ?></td>
                        <td>
                            <?php if ($candidate['whatsapp_verified']): ?>
                                <span class="verified-badge"><i class="fas fa-check-circle"></i> Verified</span>
                            <?php else: ?>
                                <span class="pending-badge"><i class="fas fa-clock"></i> Pending</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d M Y', strtotime($candidate['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-btn btn-view" onclick="viewCandidate(<?php echo $candidate['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="action-btn btn-edit" onclick="editCandidate(<?php echo $candidate['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if (!$candidate['whatsapp_verified']): ?>
                                <button class="action-btn btn-verify" onclick="resendVerification(<?php echo $candidate['id']; ?>, '<?php echo $candidate['mobile_number']; ?>')">
                                    <i class="fab fa-whatsapp"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- View Candidate Modal -->
<div class="modal" id="viewCandidateModal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h3><i class="fas fa-user"></i> Candidate Details</h3>
            <button class="modal-close" onclick="closeModal('viewCandidate')">&times;</button>
        </div>
        <div class="modal-body" id="candidateDetails">
            <div style="text-align: center; padding: 20px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2em;"></i>
                <p>Loading...</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('viewCandidate')">Close</button>
        </div>
    </div>
</div>

<style>
.candidates-table {
    background: white;
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.08);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    text-align: left;
    padding: 15px;
    background: #f8fafc;
    font-weight: 600;
    color: #1e293b;
    border-bottom: 2px solid #e2e8f0;
}

.data-table td {
    padding: 15px;
    border-bottom: 1px solid #e2e8f0;
    color: #334155;
}

.data-table tr:hover {
    background: #f8fafc;
}

.verified-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #d1fae5;
    color: #065f46;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 500;
}

.pending-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: #fef3c7;
    color: #92400e;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.85em;
    font-weight: 500;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.action-btn {
    padding: 6px 10px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 0.9em;
}

.btn-view {
    background: #e2e8f0;
    color: #475569;
}

.btn-view:hover {
    background: #cbd5e1;
}

.btn-edit {
    background: #dbeafe;
    color: #1e40af;
}

.btn-edit:hover {
    background: #bfdbfe;
}

.btn-verify {
    background: #dcfce7;
    color: #166534;
}

.btn-verify:hover {
    background: #bbf7d0;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 2000;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(5px);
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 20px;
    width: 90%;
    max-width: 600px;
    max-height: 90vh;
    overflow-y: auto;
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    padding: 20px 25px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 20px 20px 0 0;
    color: white;
}

.modal-header h3 {
    display: flex;
    align-items: center;
    gap: 10px;
    color: white;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: white;
}

.modal-body {
    padding: 25px;
}

.modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.candidate-detail-row {
    display: flex;
    padding: 12px 0;
    border-bottom: 1px solid #e2e8f0;
}

.candidate-detail-label {
    width: 140px;
    font-weight: 600;
    color: #475569;
}

.candidate-detail-value {
    flex: 1;
    color: #1e293b;
}

.detail-section {
    margin-top: 20px;
}

.detail-section h4 {
    color: #667eea;
    margin-bottom: 10px;
}

.photo-preview {
    text-align: center;
    margin: 20px 0;
}

.photo-preview img {
    max-width: 200px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}
</style>

<script>
function viewCandidate(id) {
    const modal = document.getElementById('viewCandidateModal');
    const detailsDiv = document.getElementById('candidateDetails');
    
    modal.classList.add('active');
    detailsDiv.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin" style="font-size: 2em;"></i><p>Loading...</p></div>';
    
    // Fetch candidate details via AJAX
    fetch(`get_candidate.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const candidate = data.candidate;
                let html = `
                    <div class="photo-preview">
                        ${candidate.photo_url ? `<img src="${candidate.photo_url}" alt="Photo">` : '<i class="fas fa-user-circle" style="font-size: 100px; color: #cbd5e1;"></i>'}
                    </div>
                    <div class="candidate-detail-row">
                        <div class="candidate-detail-label">Candidate ID:</div>
                        <div class="candidate-detail-value"><strong>${candidate.candidate_id || 'N/A'}</strong></div>
                    </div>
                    <div class="candidate-detail-row">
                        <div class="candidate-detail-label">Name (English):</div>
                        <div class="candidate-detail-value">${candidate.candidate_name_en || 'N/A'}</div>
                    </div>
                    <div class="candidate-detail-row">
                        <div class="candidate-detail-label">Name (Hindi):</div>
                        <div class="candidate-detail-value">${candidate.candidate_name_hi || 'N/A'}</div>
                    </div>
                    <div class="candidate-detail-row">
                        <div class="candidate-detail-label">Jila Parishad/Pradhan:</div>
                        <div class="candidate-detail-value">${candidate.jila_parishad_pradhan_text || 'N/A'}</div>
                    </div>
                    <div class="candidate-detail-row">
                        <div class="candidate-detail-label">District:</div>
                        <div class="candidate-detail-value">${candidate.district_name || 'N/A'}</div>
                    </div>
                    <div class="candidate-detail-row">
                        <div class="candidate-detail-label">Block:</div>
                        <div class="candidate-detail-value">${candidate.block_name || 'N/A'}</div>
                    </div>
                    <div class="candidate-detail-row">
                        <div class="candidate-detail-label">Panchayat:</div>
                        <div class="candidate-detail-value">${candidate.panchayat_name || 'N/A'}</div>
                    </div>
                    <div class="candidate-detail-row">
                        <div class="candidate-detail-label">Village:</div>
                        <div class="candidate-detail-value">${candidate.village || 'N/A'}</div>
                    </div>
                    <div class="candidate-detail-row">
                        <div class="candidate-detail-label">Gender:</div>
                        <div class="candidate-detail-value">${candidate.gender || 'N/A'}</div>
                    </div>
                    <div class="candidate-detail-row">
                        <div class="candidate-detail-label">Age:</div>
                        <div class="candidate-detail-value">${candidate.age || 'N/A'}</div>
                    </div>
                    <div class="candidate-detail-row">
                        <div class="candidate-detail-label">Education:</div>
                        <div class="candidate-detail-value">${candidate.education || 'N/A'}</div>
                    </div>
                    <div class="candidate-detail-row">
                        <div class="candidate-detail-label">Profession:</div>
                        <div class="candidate-detail-value">${candidate.profession || 'N/A'}</div>
                    </div>
                    <div class="candidate-detail-row">
                        <div class="candidate-detail-label">Relation:</div>
                        <div class="candidate-detail-value">${candidate.relation_type === 'father' ? 'पुत्र' : 'पत्नी'} of ${candidate.relation_name || 'N/A'}</div>
                    </div>
                    <div class="candidate-detail-row">
                        <div class="candidate-detail-label">Mobile:</div>
                        <div class="candidate-detail-value">${candidate.mobile_number || 'N/A'}</div>
                    </div>
                    <div class="candidate-detail-row">
                        <div class="candidate-detail-label">WhatsApp Verified:</div>
                        <div class="candidate-detail-value">
                            ${candidate.whatsapp_verified ? 
                                '<span class="verified-badge"><i class="fas fa-check-circle"></i> Yes</span>' : 
                                '<span class="pending-badge"><i class="fas fa-clock"></i> No</span>'}
                        </div>
                    </div>
                `;
                
                if (candidate.short_notes_hi) {
                    html += `
                        <div class="detail-section">
                            <h4><i class="fas fa-pen"></i> Short Notes</h4>
                            <p style="background: #f8fafc; padding: 15px; border-radius: 10px;">${candidate.short_notes_hi}</p>
                        </div>
                    `;
                }
                
                if (candidate.bio_hi || candidate.bio_en) {
                    html += `
                        <div class="detail-section">
                            <h4><i class="fas fa-file-alt"></i> Bio</h4>
                            <div style="background: #f8fafc; padding: 15px; border-radius: 10px;">
                                ${candidate.bio_hi ? `<p><strong>Hindi:</strong> ${candidate.bio_hi}</p>` : ''}
                                ${candidate.bio_en ? `<p><strong>English:</strong> ${candidate.bio_en}</p>` : ''}
                            </div>
                        </div>
                    `;
                }
                
                detailsDiv.innerHTML = html;
            } else {
                detailsDiv.innerHTML = '<div class="alert alert-error">Failed to load candidate details</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            detailsDiv.innerHTML = '<div class="alert alert-error">Error loading candidate details</div>';
        });
}

function editCandidate(id) {
    window.location.href = `edit_candidate.php?id=${id}`;
}

function resendVerification(id, mobile) {
    if (confirm(`Resend verification code to ${mobile}?`)) {
        const formData = new URLSearchParams();
        formData.append('ajax_action', 'resend_verification_code');
        formData.append('candidate_id', id);
        
        fetch('../index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Verification code resent successfully!');
            } else {
                alert('Failed to resend code: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to resend code');
        });
    }
}

function closeModal(modalId) {
    document.getElementById(modalId + 'Modal').classList.remove('active');
}

// Close modal when clicking outside
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});
</script>

<?php include 'admin/includes/footer.php'; ?>