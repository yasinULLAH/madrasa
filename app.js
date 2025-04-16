function showMessage(message, type) {
    const messageDiv = document.getElementById('message');
    messageDiv.textContent = message;
    messageDiv.className = `message ${type}`;
    messageDiv.style.display = 'block';
    setTimeout(() => {
        messageDiv.style.display = 'none';
    }, 3000);
}

function translateStatus(status) {
    const statusMap = {
        active: 'فعال',
        inactive: 'غیر فعال',
        present: 'موجود',
        absent: 'غیر موجود',
        leave: 'رخصت',
        pending: 'زیر التوا',
        paid: 'ادا شدہ'
    };
    return statusMap[status] || status;
}

async function loadClassesIntoDropdown(dropdownId) {
    try {
        const classes = await getAllData(STORES.CLASSES);
        populateDropdown(dropdownId, classes, 'id', 'className');
    } catch (error) {
        console.error("Error loading classes into dropdown:", error);
    }
}

function setupNavigation() {
    const navItems = document.querySelectorAll('nav ul li');
    const modules = document.querySelectorAll('.module');

    navItems.forEach(item => {
        item.addEventListener('click', () => {
            const moduleName = item.getAttribute('data-module');

            navItems.forEach(nav => nav.classList.remove('active'));
            item.classList.add('active');

            modules.forEach(module => module.classList.remove('active'));
            const targetModule = document.getElementById(`${moduleName}-module`);
            if (targetModule) {
                targetModule.classList.add('active');
                loadModuleData(moduleName);
            }
        });
    });

    const dashboardNav = document.querySelector('nav ul li[data-module="dashboard"]');
    if (dashboardNav) {
        dashboardNav.classList.add('active');
        document.getElementById('dashboard-module').classList.add('active');
    }
}

function setupStudentModule() {
    const studentForm = document.getElementById('student-form');
    const studentsTableBody = document.getElementById('students-table')?.querySelector('tbody');
    const filterClassDropdown = document.getElementById('filter-student-class');

    async function saveStudent() {
        if (!db || !studentForm.checkValidity()) {
            showMessage('براہ کرم تمام ضروری خانے پُر کریں۔', 'error');
            studentForm.reportValidity();
            return;
        }

        const studentData = {
            studentName: document.getElementById('student-name').value.trim(),
            fatherName: document.getElementById('father-name').value.trim(),
            studentPhone: document.getElementById('student-phone').value.trim(),
            studentAddress: document.getElementById('student-address').value.trim(),
            admissionNo: document.getElementById('admission-no').value.trim(),
            studentClass: document.getElementById('student-class').value,
            studentStatus: document.getElementById('student-status').value
        };

        const studentId = document.getElementById('student-id').value;

        try {
            const existingStudent = await getAllData(STORES.STUDENTS, 'admissionNo', studentData.admissionNo);
            if (existingStudent.length > 0 && existingStudent[0].id !== parseInt(studentId)) {
                showMessage('یہ داخلہ نمبر پہلے سے موجود ہے۔', 'error');
                return;
            }

            if (studentId) {
                studentData.id = parseInt(studentId);
                await updateData(STORES.STUDENTS, studentData);
                showMessage('طالب علم کی معلومات اپ ڈیٹ ہو گئیں۔', 'success');
            } else {
                await addData(STORES.STUDENTS, studentData);
                showMessage('نیا طالب علم شامل کر دیا گیا۔', 'success');
            }
            clearStudentForm();
            loadStudents();
        } catch (error) {
            console.error("Error saving student:", error);
            showMessage('طالب علم کو محفوظ کرنے میں خرابی۔', 'error');
        }
    }

    async function loadStudents(classId = '') {
        if (!db || !studentsTableBody) return;

        studentsTableBody.innerHTML = '<tr><td colspan="8" class="text-center">لوڈ ہو رہا ہے...</td></tr>';

        try {
            let students = await getAllData(STORES.STUDENTS);
            const classes = await getAllData(STORES.CLASSES);

            if (classId) {
                students = students.filter(student => student.studentClass === classId);
            }

            studentsTableBody.innerHTML = students.length === 0 ? '<tr><td colspan="8" class="text-center">کوئی طالب علم نہیں ملا۔</td></tr>' : '';

            students.sort((a, b) => a.studentName.localeCompare(b.studentName, 'ur'));

            let serial = 1;
            students.forEach(student => {
                const cls = classes.find(c => c.id === parseInt(student.studentClass));
                const row = studentsTableBody.insertRow();
                row.innerHTML = `
                    <td>${serial++}</td>
                    <td>${student.studentName}</td>
                    <td>${student.fatherName}</td>
                    <td>${student.studentPhone || '-'}</td>
                    <td>${student.admissionNo}</td>
                    <td>${cls ? cls.className : '-'}</td>
                    <td>${translateStatus(student.studentStatus)}</td>
                    <td class="no-print">
                        <button class="edit-btn" onclick="editStudent(${student.id})">ترمیم</button>
                        <button class="delete-btn" onclick="deleteStudent(${student.id})">حذف</button>
                    </td>
                `;
            });
        } catch (error) {
            console.error("Error loading students:", error);
            showMessage('طلباء کو لوڈ کرنے میں خرابی۔', 'error');
        }
    }

    window.editStudent = async function(id) {
        try {
            const student = await getData(STORES.STUDENTS, id);
            if (student) {
                document.getElementById('student-id').value = student.id;
                document.getElementById('student-name').value = student.studentName;
                document.getElementById('father-name').value = student.fatherName;
                document.getElementById('student-phone').value = student.studentPhone;
                document.getElementById('student-address').value = student.studentAddress;
                document.getElementById('admission-no').value = student.admissionNo;
                document.getElementById('student-class').value = student.studentClass;
                document.getElementById('student-status').value = student.studentStatus;
                studentForm.scrollIntoView({ behavior: 'smooth' });
            }
        } catch (error) {
            console.error("Error fetching student:", error);
            showMessage('طالب علم کی معلومات حاصل کرنے میں خرابی۔', 'error');
        }
    };

    window.deleteStudent = async function(id) {
        if (confirm('کیا آپ واقعی اس طالب علم کو حذف کرنا چاہتے ہیں؟')) {
            try {
                await deleteData(STORES.STUDENTS, id);
                showMessage('طالب علم حذف کر دیا گیا۔', 'success');
                loadStudents(filterClassDropdown.value);
            } catch (error) {
                console.error("Error deleting student:", error);
                showMessage('طالب علم کو حذف کرنے میں خرابی۔', 'error');
            }
        }
    };

    function clearStudentForm() {
        studentForm.reset();
        document.getElementById('student-id').value = '';
        document.getElementById('student-status').value = 'active';
    }

    document.getElementById('save-student-btn')?.addEventListener('click', saveStudent);
    document.getElementById('clear-student-form-btn')?.addEventListener('click', clearStudentForm);

    filterClassDropdown?.addEventListener('change', () => {
        loadStudents(filterClassDropdown.value);
    });
}

function setupSettingsModule() {
    const settingsForm = document.getElementById('settings-form');

    async function saveSettings() {
        if (!db || !settingsForm.checkValidity()) {
            showMessage('براہ کرم تمام ضروری خانے پُر کریں۔', 'error');
            settingsForm.reportValidity();
            return;
        }

        const logoFile = document.getElementById('madrasa-logo').files[0];
        const stampFile = document.getElementById('madrasa-stamp').files[0];

        const settingsData = {
            id: 'config',
            madrasaName: document.getElementById('madrasa-name').value.trim(),
            madrasaAddress: document.getElementById('madrasa-address').value.trim(),
            madrasaPhone: document.getElementById('madrasa-phone').value.trim(),
            logoDataUrl: '',
            stampDataUrl: ''
        };

        try {
            if (logoFile) {
                settingsData.logoDataUrl = await readFileAsDataURL(logoFile);
            } else {
                const existing = await getData(STORES.SETTINGS, 'config');
                if (existing?.logoDataUrl) settingsData.logoDataUrl = existing.logoDataUrl;
            }

            if (stampFile) {
                settingsData.stampDataUrl = await readFileAsDataURL(stampFile);
            } else {
                const existing = await getData(STORES.SETTINGS, 'config');
                if (existing?.stampDataUrl) settingsData.stampDataUrl = existing.stampDataUrl;
            }

            await updateData(STORES.SETTINGS, settingsData);
            showMessage('ترتیبات محفوظ ہو گئیں۔', 'success');
            loadSettings();
        } catch (error) {
            console.error("Error saving settings:", error);
            showMessage('ترتیبات محفوظ کرنے میں خرابی۔', 'error');
        }
    }

    async function loadSettings() {
        try {
            const settings = await getData(STORES.SETTINGS, 'config');
            if (settings) {
                document.getElementById('madrasa-name').value = settings.madrasaName || '';
                document.getElementById('madrasa-address').value = settings.madrasaAddress || '';
                document.getElementById('madrasa-phone').value = settings.madrasaPhone || '';
            }
        } catch (error) {
            console.error("Error loading settings:", error);
        }
    }

    function readFileAsDataURL(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = () => reject(reader.error);
            reader.readAsDataURL(file);
        });
    }

    document.getElementById('save-settings-btn')?.addEventListener('click', saveSettings);
}

document.addEventListener('DOMContentLoaded', async () => {
    try {
        await openDatabase();
        setupNavigation();
        setupStudentModule();
        setupSettingsModule();
        loadModuleData('dashboard');
    } catch (error) {
        console.error("Initialization failed:", error);
        showMessage('ایپلیکیشن شروع کرنے میں ناکامی۔', 'error');
    }
});