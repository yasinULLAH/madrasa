
async function loadStudents(classId = '') {
    const studentsTableBody = document.getElementById('students-table')?.querySelector('tbody');
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

// Update setupStudentModule
function setupStudentModule() {
    const studentForm = document.getElementById('student-form');
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

    loadClassesIntoDropdown('student-class');
    loadClassesIntoDropdown('filter-student-class');
}

// Update setupSettingsModule
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

// Update loadModuleData to use the new functions
function loadModuleData(moduleName) {
    console.log(`Loading data for: ${moduleName}`);
    switch (moduleName) {
        case 'dashboard':
            break;
        case 'students':
            loadStudents();
            break;
        case 'teachers':
            loadTeachers();
            break;
        case 'classes':
            loadClasses();
            break;
        case 'subjects':
            loadSubjects();
            break;
        case 'attendance':
            loadAttendance();
            break;
        case 'exams':
            loadExams();
            loadMarks();
            break;
        case 'fees':
            loadFees();
            break;
        case 'salaries':
            loadSalaries();
            break;
        case 'ledger':
            loadLedger();
            break;
        case 'reports':
            loadReports();
            break;
        case 'settings':
            loadSettings();
            break;
    }
}

// Update DOMContentLoaded
document.addEventListener('DOMContentLoaded', async () => {
    try {
        await openDatabase();
        setupNavigation();
        setupStudentModule();
        setupTeacherModule();
        setupClassModule();
        setupSubjectModule();
        setupAttendanceModule();
        setupExamModule();
        setupFeeModule();
        setupSalaryModule();
        setupLedgerModule();
        setupReportModule();
        setupSettingsModule();
        loadSettings();
        loadModuleData('dashboard');
    } catch (error) {
        console.error("Initialization failed:", error);
        showMessage('ایپلیکیشن شروع کرنے میں ناکامی۔ تفصیلات کے لیے کنسول چیک کریں۔', 'error');
    }
});

const TEACHER_FORM_FIELDS = {
    id: 'teacher-id',
    name: 'teacher-name',
    phone: 'teacher-phone',
    address: 'teacher-address',
    qualification: 'teacher-qualification',
    salary: 'teacher-salary',
    subjects: 'teacher-subjects',
    status: 'teacher-status'
};

const CLASS_FORM_FIELDS = {
    id: 'class-id',
    name: 'class-name',
    description: 'class-description'
};

const SUBJECT_FORM_FIELDS = {
    id: 'subject-id',
    name: 'subject-name',
    classId: 'subject-class',
    teacherId: 'subject-teacher'
};

const ATTENDANCE_FORM_FIELDS = {
    date: 'attendance-date',
    classId: 'attendance-class',
    studentId: 'attendance-student',
    status: 'attendance-status'
};

const EXAM_FORM_FIELDS = {
    id: 'exam-id',
    name: 'exam-name',
    date: 'exam-date',
    classId: 'exam-class'
};

const MARKS_FORM_FIELDS = {
    id: 'mark-id',
    examId: 'mark-exam',
    studentId: 'mark-student',
    subjectId: 'mark-subject',
    marksObtained: 'mark-obtained',
    totalMarks: 'mark-total'
};

const FEE_FORM_FIELDS = {
    id: 'fee-id',
    studentId: 'fee-student',
    monthYear: 'fee-month-year',
    amountDue: 'fee-amount-due',
    amountPaid: 'fee-amount-paid',
    paymentDate: 'fee-payment-date',
    status: 'fee-status'
};

const SALARY_FORM_FIELDS = {
    id: 'salary-id',
    teacherId: 'salary-teacher',
    monthYear: 'salary-month-year',
    amount: 'salary-amount',
    paymentDate: 'salary-payment-date',
    status: 'salary-status'
};

const LEDGER_FORM_FIELDS = {
    id: 'ledger-id',
    type: 'ledger-type',
    relatedId: 'ledger-related-id',
    amount: 'ledger-amount',
    date: 'ledger-date',
    description: 'ledger-description'
};

function populateDropdown(dropdownId, items, valueField, textField) {
    const dropdown = document.getElementById(dropdownId);
    if (!dropdown) return;
    dropdown.innerHTML = '<option value="">منتخب کریں</option>';
    items.forEach(item => {
        const option = document.createElement('option');
        option.value = item[valueField];
        option.textContent = item[textField];
        dropdown.appendChild(option);
    });
}

async function loadTeachersIntoDropdown(dropdownId) {
    try {
        const teachers = await getAllData(STORES.TEACHERS);
        populateDropdown(dropdownId, teachers, 'id', 'teacherName');
    } catch (error) {
        console.error("Error loading teachers into dropdown:", error);
    }
}

async function loadStudentsIntoDropdown(dropdownId) {
    try {
        const students = await getAllData(STORES.STUDENTS);
        populateDropdown(dropdownId, students, 'id', 'studentName');
    } catch (error) {
        console.error("Error loading students into dropdown:", error);
    }
}

async function loadExamsIntoDropdown(dropdownId) {
    try {
        const exams = await getAllData(STORES.EXAMS);
        populateDropdown(dropdownId, exams, 'id', 'examName');
    } catch (error) {
        console.error("Error loading exams into dropdown:", error);
    }
}

async function loadSubjectsIntoDropdown(dropdownId) {
    try {
        const subjects = await getAllData(STORES.SUBJECTS);
        populateDropdown(dropdownId, subjects, 'id', 'subjectName');
    } catch (error) {
        console.error("Error loading subjects into dropdown:", error);
    }
}

function setupDashboardModule() {
    async function loadDashboard() {
        const dashboardModule = document.getElementById('dashboard-module');
        if (!db || !dashboardModule) return;

        try {
            const students = await getAllData(STORES.STUDENTS);
            const teachers = await getAllData(STORES.TEACHERS);
            const fees = await getAllData(STORES.FEES);
            const today = new Date().toISOString().slice(0, 10);
            const todayFees = fees.filter(f => f.paymentDate === today && f.status === 'paid');

            const totalStudents = students.length;
            const activeStudents = students.filter(s => s.studentStatus === 'active').length;
            const totalTeachers = teachers.length;
            const todayFeeCollection = todayFees.reduce((sum, f) => sum + f.amountPaid, 0);

            dashboardModule.innerHTML = `
                <h2>ڈیش بورڈ</h2>
                <div class="d-flex justify-between">
                    <div style="flex: 1; margin: 10px;">
                        <h3>طلباء</h3>
                        <p>کل طلباء: ${totalStudents}</p>
                        <p>فعال طلباء: ${activeStudents}</p>
                    </div>
                    <div style="flex: 1; margin: 10px;">
                        <h3>اساتذہ</h3>
                        <p>کل اساتذہ: ${totalTeachers}</p>
                    </div>
                    <div style="flex: 1; margin: 10px;">
                        <h3>آج کی فیس وصولی</h3>
                        <p>رقم: ${todayFeeCollection} PKR</p>
                    </div>
                </div>
            `;
        } catch (error) {
            console.error("Error loading dashboard:", error);
            showMessage('ڈیش بورڈ لوڈ کرنے میں خرابی۔', 'error');
        }
    }

    loadDashboard();
}

function setupTeacherModule() {
    const teacherForm = document.getElementById('teacher-form');
    const teachersTableBody = document.getElementById('teachers-table')?.querySelector('tbody');

    async function saveTeacher() {
        if (!db || !teacherForm.checkValidity()) {
            showMessage('براہ کرم تمام ضروری خانے پُر کریں۔', 'error');
            teacherForm.reportValidity();
            return;
        }

        const teacherData = {
            teacherName: document.getElementById(TEACHER_FORM_FIELDS.name).value.trim(),
            teacherPhone: document.getElementById(TEACHER_FORM_FIELDS.phone).value.trim(),
            teacherAddress: document.getElementById(TEACHER_FORM_FIELDS.address).value.trim(),
            qualification: document.getElementById(TEACHER_FORM_FIELDS.qualification).value.trim(),
            salary: parseFloat(document.getElementById(TEACHER_FORM_FIELDS.salary).value) || 0,
            subjects: document.getElementById(TEACHER_FORM_FIELDS.subjects).value.split(',').map(s => s.trim()).filter(s => s),
            teacherStatus: document.getElementById(TEACHER_FORM_FIELDS.status).value
        };

        const teacherId = document.getElementById(TEACHER_FORM_FIELDS.id).value;

        try {
            const existingTeacher = await getAllData(STORES.TEACHERS, 'phone', teacherData.teacherPhone);
            if (existingTeacher.length > 0 && existingTeacher[0].id !== parseInt(teacherId)) {
                showMessage('یہ فون نمبر پہلے سے موجود ہے۔', 'error');
                return;
            }

            if (teacherId) {
                teacherData.id = parseInt(teacherId);
                await updateData(STORES.TEACHERS, teacherData);
                showMessage('استاد کی معلومات اپ ڈیٹ ہو گئیں۔', 'success');
            } else {
                await addData(STORES.TEACHERS, teacherData);
                showMessage('نیا استاد شامل کر دیا گیا۔', 'success');
            }
            clearTeacherForm();
            loadTeachers();
            loadTeachersIntoDropdown(SUBJECT_FORM_FIELDS.teacherId);
            loadTeachersIntoDropdown(SALARY_FORM_FIELDS.teacherId);
        } catch (error) {
            console.error("Error saving teacher:", error);
            showMessage('استاد کو محفوظ کرنے میں خرابی۔', 'error');
        }
    }

    async function loadTeachers() {
        if (!db || !teachersTableBody) return;

        teachersTableBody.innerHTML = '<tr><td colspan="7" class="text-center">لوڈ ہو رہا ہے...</td></tr>';

        try {
            const teachers = await getAllData(STORES.TEACHERS);
            teachersTableBody.innerHTML = teachers.length === 0 ? '<tr><td colspan="7" class="text-center">کوئی استاد نہیں ملا۔</td></tr>' : '';

            teachers.sort((a, b) => a.teacherName.localeCompare(b.teacherName, 'ur'));

            let serial = 1;
            teachers.forEach(teacher => {
                const row = teachersTableBody.insertRow();
                row.innerHTML = `
                    <td>${serial++}</td>
                    <td>${teacher.teacherName}</td>
                    <td>${teacher.teacherPhone}</td>
                    <td>${teacher.qualification}</td>
                    <td>${teacher.salary}</td>
                    <td>${teacher.subjects.join(', ')}</td>
                    <td class="no-print">
                        <button class="edit-btn" onclick="editTeacher(${teacher.id})">ترمیم</button>
                        <button class="delete-btn" onclick="deleteTeacher(${teacher.id})">حذف</button>
                    </td>
                `;
            });
        } catch (error) {
            console.error("Error loading teachers:", error);
            showMessage('اساتذہ کو لوڈ کرنے میں خرابی۔', 'error');
        }
    }

    window.editTeacher = async function(id) {
        try {
            const teacher = await getData(STORES.TEACHERS, id);
            if (teacher) {
                document.getElementById(TEACHER_FORM_FIELDS.id).value = teacher.id;
                document.getElementById(TEACHER_FORM_FIELDS.name).value = teacher.teacherName;
                document.getElementById(TEACHER_FORM_FIELDS.phone).value = teacher.teacherPhone;
                document.getElementById(TEACHER_FORM_FIELDS.address).value = teacher.teacherAddress;
                document.getElementById(TEACHER_FORM_FIELDS.qualification).value = teacher.qualification;
                document.getElementById(TEACHER_FORM_FIELDS.salary).value = teacher.salary;
                document.getElementById(TEACHER_FORM_FIELDS.subjects).value = teacher.subjects.join(', ');
                document.getElementById(TEACHER_FORM_FIELDS.status).value = teacher.teacherStatus;
                teacherForm.scrollIntoView({ behavior: 'smooth' });
            }
        } catch (error) {
            console.error("Error fetching teacher:", error);
            showMessage('استاد کی معلومات حاصل کرنے میں خرابی۔', 'error');
        }
    };

    window.deleteTeacher = async function(id) {
        if (confirm('کیا آپ واقعی اس استاد کو حذف کرنا چاہتے ہیں؟')) {
            try {
                await deleteData(STORES.TEACHERS, id);
                showMessage('استاد حذف کر دیا گیا۔', 'success');
                loadTeachers();
                loadTeachersIntoDropdown(SUBJECT_FORM_FIELDS.teacherId);
                loadTeachersIntoDropdown(SALARY_FORM_FIELDS.teacherId);
            } catch (error) {
                console.error("Error deleting teacher:", error);
                showMessage('استاد کو حذف کرنے میں خرابی۔', 'error');
            }
        }
    };

    function clearTeacherForm() {
        teacherForm.reset();
        document.getElementById(TEACHER_FORM_FIELDS.id).value = '';
        document.getElementById(TEACHER_FORM_FIELDS.status).value = 'active';
    }

    document.getElementById('save-teacher-btn')?.addEventListener('click', saveTeacher);
    document.getElementById('clear-teacher-form-btn')?.addEventListener('click', clearTeacherForm);
    loadTeachers();
}

function setupClassesModule() {
    const classForm = document.getElementById('class-form');
    const classesTableBody = document.getElementById('classes-table')?.querySelector('tbody');

    async function saveClass() {
        if (!db || !classForm.checkValidity()) {
            showMessage('براہ کرم تمام ضروری خانے پُر کریں۔', 'error');
            classForm.reportValidity();
            return;
        }

        const classData = {
            className: document.getElementById(CLASS_FORM_FIELDS.name).value.trim(),
            description: document.getElementById(CLASS_FORM_FIELDS.description).value.trim()
        };

        const classId = document.getElementById(CLASS_FORM_FIELDS.id).value;

        try {
            const existingClass = await getAllData(STORES.CLASSES, 'name', classData.className);
            if (existingClass.length > 0 && existingClass[0].id !== parseInt(classId)) {
                showMessage('یہ کلاس کا نام پہلے سے موجود ہے۔', 'error');
                return;
            }

            if (classId) {
                classData.id = parseInt(classId);
                await updateData(STORES.CLASSES, classData);
                showMessage('کلاس اپ ڈیٹ ہو گئی۔', 'success');
            } else {
                await addData(STORES.CLASSES, classData);
                showMessage('نیا کلاس شامل کر دیا گیا۔', 'success');
            }
            clearClassForm();
            loadClasses();
            loadClassesIntoDropdown('student-class');
            loadClassesIntoDropdown('filter-student-class');
            loadClassesIntoDropdown(SUBJECT_FORM_FIELDS.classId);
            loadClassesIntoDropdown(ATTENDANCE_FORM_FIELDS.classId);
            loadClassesIntoDropdown(EXAM_FORM_FIELDS.classId);
        } catch (error) {
            console.error("Error saving class:", error);
            showMessage('کلاس محفوظ کرنے میں خرابی۔', 'error');
        }
    }

    async function loadClasses() {
        if (!db || !classesTableBody) return;

        classesTableBody.innerHTML = '<tr><td colspan="4" class="text-center">لوڈ ہو رہا ہے...</td></tr>';

        try {
            const classes = await getAllData(STORES.CLASSES);
            classesTableBody.innerHTML = classes.length === 0 ? '<tr><td colspan="4" class="text-center">کوئی کلاس نہیں ملی۔</td></tr>' : '';

            classes.sort((a, b) => a.className.localeCompare(b.className, 'ur'));

            let serial = 1;
            classes.forEach(cls => {
                const row = classesTableBody.insertRow();
                row.innerHTML = `
                    <td>${serial++}</td>
                    <td>${cls.className}</td>
                    <td>${cls.description || '-'}</td>
                    <td class="no-print">
                        <button class="edit-btn" onclick="editClass(${cls.id})">ترمیم</button>
                        <button class="delete-btn" onclick="deleteClass(${cls.id})">حذف</button>
                    </td>
                `;
            });
        } catch (error) {
            console.error("Error loading classes:", error);
            showMessage('کلاسیں لوڈ کرنے میں خرابی۔', 'error');
        }
    }

    window.editClass = async function(id) {
        try {
            const cls = await getData(STORES.CLASSES, id);
            if (cls) {
                document.getElementById(CLASS_FORM_FIELDS.id).value = cls.id;
                document.getElementById(CLASS_FORM_FIELDS.name).value = cls.className;
                document.getElementById(CLASS_FORM_FIELDS.description).value = cls.description;
                classForm.scrollIntoView({ behavior: 'smooth' });
            }
        } catch (error) {
            console.error("Error fetching class:", error);
            showMessage('کلاس کی معلومات حاصل کرنے میں خرابی۔', 'error');
        }
    };

    window.deleteClass = async function(id) {
        if (confirm('کیا آپ واقعی اس کلاس کو حذف کرنا چاہتے ہیں؟')) {
            try {
                await deleteData(STORES.CLASSES, id);
                showMessage('کلاس حذف کر دی گئی۔', 'success');
                loadClasses();
                loadClassesIntoDropdown('student-class');
                loadClassesIntoDropdown('filter-student-class');
                loadClassesIntoDropdown(SUBJECT_FORM_FIELDS.classId);
                loadClassesIntoDropdown(ATTENDANCE_FORM_FIELDS.classId);
                loadClassesIntoDropdown(EXAM_FORM_FIELDS.classId);
            } catch (error) {
                console.error("Error deleting class:", error);
                showMessage('کلاس حذف کرنے میں خرابی۔', 'error');
            }
        }
    };

    function clearClassForm() {
        classForm.reset();
        document.getElementById(CLASS_FORM_FIELDS.id).value = '';
    }

    document.getElementById('save-class-btn')?.addEventListener('click', saveClass);
    document.getElementById('clear-class-form-btn')?.addEventListener('click', clearClassForm);
    loadClasses();
}

function setupSubjectsModule() {
    const subjectForm = document.getElementById('subject-form');
    const subjectsTableBody = document.getElementById('subjects-table')?.querySelector('tbody');

    async function saveSubject() {
        if (!db || !subjectForm.checkValidity()) {
            showMessage('براہ کرم تمام ضروری خانے پُر کریں۔', 'error');
            subjectForm.reportValidity();
            return;
        }

        const subjectData = {
            subjectName: document.getElementById(SUBJECT_FORM_FIELDS.name).value.trim(),
            classId: document.getElementById(SUBJECT_FORM_FIELDS.classId).value,
            teacherId: document.getElementById(SUBJECT_FORM_FIELDS.teacherId).value
        };

        const subjectId = document.getElementById(SUBJECT_FORM_FIELDS.id).value;

        try {
            if (subjectId) {
                subjectData.id = parseInt(subjectId);
                await updateData(STORES.SUBJECTS, subjectData);
                showMessage('مضمون اپ ڈیٹ ہو گیا۔', 'success');
            } else {
                await addData(STORES.SUBJECTS, subjectData);
                showMessage('نیا مضمون شامل کر دیا گیا۔', 'success');
            }
            clearSubjectForm();
            loadSubjects();
            loadSubjectsIntoDropdown(MARKS_FORM_FIELDS.subjectId);
        } catch (error) {
            console.error("Error saving subject:", error);
            showMessage('مضمون محفوظ کرنے میں خرابی۔', 'error');
        }
    }

    async function loadSubjects() {
        if (!db || !subjectsTableBody) return;

        subjectsTableBody.innerHTML = '<tr><td colspan="5" class="text-center">لوڈ ہو رہا ہے...</td></tr>';

        try {
            const subjects = await getAllData(STORES.SUBJECTS);
            const classes = await getAllData(STORES.CLASSES);
            const teachers = await getAllData(STORES.TEACHERS);

            subjectsTableBody.innerHTML = subjects.length === 0 ? '<tr><td colspan="5" class="text-center">کوئی مضمون نہیں ملا۔</td></tr>' : '';

            let serial = 1;
            subjects.forEach(subject => {
                const cls = classes.find(c => c.id === parseInt(subject.classId));
                const teacher = teachers.find(t => t.id === parseInt(subject.teacherId));
                const row = subjectsTableBody.insertRow();
                row.innerHTML = `
                    <td>${serial++}</td>
                    <td>${subject.subjectName}</td>
                    <td>${cls ? cls.className : '-'}</td>
                    <td>${teacher ? teacher.teacherName : '-'}</td>
                    <td class="no-print">
                        <button class="edit-btn" onclick="editSubject(${subject.id})">ترمیم</button>
                        <button class="delete-btn" onclick="deleteSubject(${subject.id})">حذف</button>
                    </td>
                `;
            });
        } catch (error) {
            console.error("Error loading subjects:", error);
            showMessage('مضامین لوڈ کرنے میں خرابی۔', 'error');
        }
    }

    window.editSubject = async function(id) {
        try {
            const subject = await getData(STORES.SUBJECTS, id);
            if (subject) {
                document.getElementById(SUBJECT_FORM_FIELDS.id).value = subject.id;
                document.getElementById(SUBJECT_FORM_FIELDS.name).value = subject.subjectName;
                document.getElementById(SUBJECT_FORM_FIELDS.classId).value = subject.classId;
                document.getElementById(SUBJECT_FORM_FIELDS.teacherId).value = subject.teacherId;
                subjectForm.scrollIntoView({ behavior: 'smooth' });
            }
        } catch (error) {
            console.error("Error fetching subject:", error);
            showMessage('مضمون کی معلومات حاصل کرنے میں خرابی۔', 'error');
        }
    };

    window.deleteSubject = async function(id) {
        if (confirm('کیا آپ واقعی اس مضمون کو حذف کرنا چاہتے ہیں؟')) {
            try {
                await deleteData(STORES.SUBJECTS, id);
                showMessage('مضمون حذف کر دیا گیا۔', 'success');
                loadSubjects();
                loadSubjectsIntoDropdown(MARKS_FORM_FIELDS.subjectId);
            } catch (error) {
                console.error("Error deleting subject:", error);
                showMessage('مضمون حذف کرنے میں خرابی۔', 'error');
            }
        }
    };

    function clearSubjectForm() {
        subjectForm.reset();
        document.getElementById(SUBJECT_FORM_FIELDS.id).value = '';
    }

    document.getElementById('save-subject-btn')?.addEventListener('click', saveSubject);
    document.getElementById('clear-subject-form-btn')?.addEventListener('click', clearSubjectForm);
    loadSubjects();
    loadClassesIntoDropdown(SUBJECT_FORM_FIELDS.classId);
    loadTeachersIntoDropdown(SUBJECT_FORM_FIELDS.teacherId);
}

function setupAttendanceModule() {
    const attendanceForm = document.getElementById('attendance-form');
    const attendanceTableBody = document.getElementById('attendance-table')?.querySelector('tbody');

    async function saveAttendance() {
        if (!db || !attendanceForm.checkValidity()) {
            showMessage('براہ کرم تمام ضروری خانے پُر کریں۔', 'error');
            attendanceForm.reportValidity();
            return;
        }

        const attendanceData = {
            date: document.getElementById(ATTENDANCE_FORM_FIELDS.date).value,
            classId: document.getElementById(ATTENDANCE_FORM_FIELDS.classId).value,
            studentId: document.getElementById(ATTENDANCE_FORM_FIELDS.studentId).value,
            status: document.getElementById(ATTENDANCE_FORM_FIELDS.status).value
        };

        try {
            const existing = await getAllData(STORES.ATTENDANCE_STUDENTS, 'date_student', [attendanceData.date, attendanceData.studentId]);
            if (existing.length > 0) {
                showMessage('اس طالب علم کی اس تاریخ کے لیے حاضری پہلے سے درج ہے۔', 'error');
                return;
            }

            await addData(STORES.ATTENDANCE_STUDENTS, attendanceData);
            showMessage('حاضری درج کر دی گئی۔', 'success');
            clearAttendanceForm();
            loadAttendance();
        } catch (error) {
            console.error("Error saving attendance:", error);
            showMessage('حاضری درج کرنے میں خرابی۔', 'error');
        }
    }

    async function loadAttendance() {
        if (!db || !attendanceTableBody) return;

        attendanceTableBody.innerHTML = '<tr><td colspan="5" class="text-center">لوڈ ہو رہا ہے...</td></tr>';

        try {
            const attendances = await getAllData(STORES.ATTENDANCE_STUDENTS);
            const students = await getAllData(STORES.STUDENTS);
            const classes = await getAllData(STORES.CLASSES);

            attendanceTableBody.innerHTML = attendances.length === 0 ? '<tr><td colspan="5" class="text-center">کوئی حاضری نہیں ملی۔</td></tr>' : '';

            let serial = 1;
            attendances.forEach(att => {
                const student = students.find(s => s.id === parseInt(att.studentId));
                const cls = classes.find(c => c.id === parseInt(att.classId));
                const row = attendanceTableBody.insertRow();
                row.innerHTML = `
                    <td>${serial++}</td>
                    <td>${att.date}</td>
                    <td>${student ? student.studentName : '-'}</td>
                    <td>${cls ? cls.className : '-'}</td>
                    <td>${att.status}</td>
                `;
            });
        } catch (error) {
            console.error("Error loading attendance:", error);
            showMessage('حاضری لوڈ کرنے میں خرابی۔', 'error');
        }
    }

    function clearAttendanceForm() {
        attendanceForm.reset();
    }

    document.getElementById('save-attendance-btn')?.addEventListener('click', saveAttendance);
    document.getElementById('clear-attendance-form-btn')?.addEventListener('click', clearAttendanceForm);
    loadAttendance();
    loadClassesIntoDropdown(ATTENDANCE_FORM_FIELDS.classId);
    loadStudentsIntoDropdown(ATTENDANCE_FORM_FIELDS.studentId);
}

function setupExamsModule() {
    const examForm = document.getElementById('exam-form');
    const marksForm = document.getElementById('marks-form');
    const examsTableBody = document.getElementById('exams-table')?.querySelector('tbody');
    const marksTableBody = document.getElementById('marks-table')?.querySelector('tbody');

    async function saveExam() {
        if (!db || !examForm.checkValidity()) {
            showMessage('براہ کرم تمام ضروری خانے پُر کریں۔', 'error');
            examForm.reportValidity();
            return;
        }

        const examData = {
            examName: document.getElementById(EXAM_FORM_FIELDS.name).value.trim(),
            examDate: document.getElementById(EXAM_FORM_FIELDS.date).value,
            classId: document.getElementById(EXAM_FORM_FIELDS.classId).value
        };

        const examId = document.getElementById(EXAM_FORM_FIELDS.id).value;

        try {
            if (examId) {
                examData.id = parseInt(examId);
                await updateData(STORES.EXAMS, examData);
                showMessage('امتحان اپ ڈیٹ ہو گیا۔', 'success');
            } else {
                await addData(STORES.EXAMS, examData);
                showMessage('نیا امتحان شامل کر دیا گیا۔', 'success');
            }
            clearExamForm();
            loadExams();
            loadExamsIntoDropdown(MARKS_FORM_FIELDS.examId);
        } catch (error) {
            console.error("Error saving exam:", error);
            showMessage('امتحان محفوظ کرنے میں خرابی۔', 'error');
        }
    }

    async function saveMarks() {
        if (!db || !marksForm.checkValidity()) {
            showMessage('براہ کرم تمام ضروری خانے پُر کریں۔', 'error');
            marksForm.reportValidity();
            return;
        }

        const marksData = {
            examId: document.getElementById(MARKS_FORM_FIELDS.examId).value,
            studentId: document.getElementById(MARKS_FORM_FIELDS.studentId).value,
            subjectId: document.getElementById(MARKS_FORM_FIELDS.subjectId).value,
            marksObtained: parseFloat(document.getElementById(MARKS_FORM_FIELDS.marksObtained).value) || 0,
            totalMarks: parseFloat(document.getElementById(MARKS_FORM_FIELDS.totalMarks).value) || 100
        };

        const markId = document.getElementById(MARKS_FORM_FIELDS.id).value;

        try {
            const existing = await getAllData(STORES.MARKS, 'exam_student_subject', [marksData.examId, marksData.studentId, marksData.subjectId]);
            if (existing.length > 0 && existing[0].id !== parseInt(markId)) {
                showMessage('اس طالب علم کے اس امتحان اور مضمون کے لیے نمبر پہلے سے درج ہیں۔', 'error');
                return;
            }

            if (markId) {
                marksData.id = parseInt(markId);
                await updateData(STORES.MARKS, marksData);
                showMessage('نمبر اپ ڈیٹ ہو گئے۔', 'success');
            } else {
                await addData(STORES.MARKS, marksData);
                showMessage('نمبر درج کر دیے گئے۔', 'success');
            }
            clearMarksForm();
            loadMarks();
        } catch (error) {
            console.error("Error saving marks:", error);
            showMessage('نمبر درج کرنے میں خرابی۔', 'error');
        }
    }

    async function loadExams() {
        if (!db || !examsTableBody) return;

        examsTableBody.innerHTML = '<tr><td colspan="5" class="text-center">لوڈ ہو رہا ہے...</td></tr>';

        try {
            const exams = await getAllData(STORES.EXAMS);
            const classes = await getAllData(STORES.CLASSES);

            examsTableBody.innerHTML = exams.length === 0 ? '<tr><td colspan="5" class="text-center">کوئی امتحان نہیں ملا۔</td></tr>' : '';

            let serial = 1;
            exams.forEach(exam => {
                const cls = classes.find(c => c.id === parseInt(exam.classId));
                const row = examsTableBody.insertRow();
                row.innerHTML = `
                    <td>${serial++}</td>
                    <td>${exam.examName}</td>
                    <td>${exam.examDate}</td>
                    <td>${cls ? cls.className : '-'}</td>
                    <td class="no-print">
                        <button class="edit-btn" onclick="editExam(${exam.id})">ترمیم</button>
                        <button class="delete-btn" onclick="deleteExam(${exam.id})">حذف</button>
                    </td>
                `;
            });
        } catch (error) {
            console.error("Error loading exams:", error);
            showMessage('امتحانات لوڈ کرنے میں خرابی۔', 'error');
        }
    }

    async function loadMarks() {
        if (!db || !marksTableBody) return;

        marksTableBody.innerHTML = '<tr><td colspan="7" class="text-center">لوڈ ہو رہا ہے...</td></tr>';

        try {
            const marks = await getAllData(STORES.MARKS);
            const exams = await getAllData(STORES.EXAMS);
            const students = await getAllData(STORES.STUDENTS);
            const subjects = await getAllData(STORES.SUBJECTS);

            marksTableBody.innerHTML = marks.length === 0 ? '<tr><td colspan="7" class="text-center">کوئی نمبر نہیں ملے۔</td></tr>' : '';

            let serial = 1;
            marks.forEach(mark => {
                const exam = exams.find(e => e.id === parseInt(mark.examId));
                const student = students.find(s => s.id === parseInt(mark.studentId));
                const subject = subjects.find(sub => sub.id === parseInt(mark.subjectId));
                const row = marksTableBody.insertRow();
                row.innerHTML = `
                    <td>${serial++}</td>
                    <td>${exam ? exam.examName : '-'}</td>
                    <td>${student ? student.studentName : '-'}</td>
                    <td>${subject ? subject.subjectName : '-'}</td>
                    <td>${mark.marksObtained}</td>
                    <td>${mark.totalMarks}</td>
                    <td class="no-print">
                        <button class="edit-btn" onclick="editMarks(${mark.id})">ترمیم</button>
                        <button class="delete-btn" onclick="deleteMarks(${mark.id})">حذف</button>
                    </td>
                `;
            });
        } catch (error) {
            console.error("Error loading marks:", error);
            showMessage('نمبر لوڈ کرنے میں خرابی۔', 'error');
        }
    }

    window.editExam = async function(id) {
        try {
            const exam = await getData(STORES.EXAMS, id);
            if (exam) {
                document.getElementById(EXAM_FORM_FIELDS.id).value = exam.id;
                document.getElementById(EXAM_FORM_FIELDS.name).value = exam.examName;
                document.getElementById(EXAM_FORM_FIELDS.date).value = exam.examDate;
                document.getElementById(EXAM_FORM_FIELDS.classId).value = exam.classId;
                examForm.scrollIntoView({ behavior: 'smooth' });
            }
        } catch (error) {
            console.error("Error fetching exam:", error);
            showMessage('امتحان کی معلومات حاصل کرنے میں خرابی۔', 'error');
        }
    };

    window.deleteExam = async function(id) {
        if (confirm('کیا آپ واقعی اس امتحان کو حذف کرنا چاہتے ہیں؟')) {
            try {
                await deleteData(STORES.EXAMS, id);
                showMessage('امتحان حذف کر دیا گیا۔', 'success');
                loadExams();
                loadExamsIntoDropdown(MARKS_FORM_FIELDS.examId);
            } catch (error) {
                console.error("Error deleting exam:", error);
                showMessage('امتحان حذف کرنے میں خرابی۔', 'error');
            }
        }
    };

    window.editMarks = async function(id) {
        try {
            const mark = await getData(STORES.MARKS, id);
            if (mark) {
                document.getElementById(MARKS_FORM_FIELDS.id).value = mark.id;
                document.getElementById(MARKS_FORM_FIELDS.examId).value = mark.examId;
                document.getElementById(MARKS_FORM_FIELDS.studentId).value = mark.studentId;
                document.getElementById(MARKS_FORM_FIELDS.subjectId).value = mark.subjectId;
                document.getElementById(MARKS_FORM_FIELDS.marksObtained).value = mark.marksObtained;
                document.getElementById(MARKS_FORM_FIELDS.totalMarks).value = mark.totalMarks;
                marksForm.scrollIntoView({ behavior: 'smooth' });
            }
        } catch (error) {
            console.error("Error fetching marks:", error);
            showMessage('نمبر کی معلومات حاصل کرنے میں خرابی۔', 'error');
        }
    };

    window.deleteMarks = async function(id) {
        if (confirm('کیا آپ واقعی اس نمبر کو حذف کرنا چاہتے ہیں؟')) {
            try {
                await deleteData(STORES.MARKS, id);
                showMessage('نمبر حذف کر دیا گیا۔', 'success');
                loadMarks();
            } catch (error) {
                console.error("Error deleting marks:", error);
                showMessage('نمبر حذف کرنے میں خرابی۔', 'error');
            }
        }
    };

    function clearExamForm() {
        examForm.reset();
        document.getElementById(EXAM_FORM_FIELDS.id).value = '';
    }

    function clearMarksForm() {
        marksForm.reset();
        document.getElementById(MARKS_FORM_FIELDS.id).value = '';
    }

    document.getElementById('save-exam-btn')?.addEventListener('click', saveExam);
    document.getElementById('clear-exam-form-btn')?.addEventListener('click', clearExamForm);
    document.getElementById('save-marks-btn')?.addEventListener('click', saveMarks);
    document.getElementById('clear-marks-form-btn')?.addEventListener('click', clearMarksForm);
    loadExams();
    loadMarks();
    loadClassesIntoDropdown(EXAM_FORM_FIELDS.classId);
    loadExamsIntoDropdown(MARKS_FORM_FIELDS.examId);
    loadStudentsIntoDropdown(MARKS_FORM_FIELDS.studentId);
    loadSubjectsIntoDropdown(MARKS_FORM_FIELDS.subjectId);
}

function setupFeesModule() {
    const feeForm = document.getElementById('fee-form');
    const feesTableBody = document.getElementById('fees-table')?.querySelector('tbody');

    async function saveFee() {
        if (!db || !feeForm.checkValidity()) {
            showMessage('براہ کرم تمام ضروری خانے پُر کریں۔', 'error');
            feeForm.reportValidity();
            return;
        }

        const feeData = {
            studentId: document.getElementById(FEE_FORM_FIELDS.studentId).value,
            monthYear: document.getElementById(FEE_FORM_FIELDS.monthYear).value,
            amountDue: parseFloat(document.getElementById(FEE_FORM_FIELDS.amountDue).value) || 0,
            amountPaid: parseFloat(document.getElementById(FEE_FORM_FIELDS.amountPaid).value) || 0,
            paymentDate: document.getElementById(FEE_FORM_FIELDS.paymentDate).value,
            status: document.getElementById(FEE_FORM_FIELDS.status).value
        };

        const feeId = document.getElementById(FEE_FORM_FIELDS.id).value;

        try {
            if (feeId) {
                feeData.id = parseInt(feeId);
                await updateData(STORES.FEES, feeData);
                showMessage('فیس اپ ڈیٹ ہو گئی۔', 'success');
            } else {
                await addData(STORES.FEES, feeData);
                showMessage('فیس درج کر دی گئی۔', 'success');
                // Add to ledger
                await addData(STORES.LEDGER, {
                    type: 'fee',
                    relatedId: feeData.studentId,
                    amount: feeData.amountPaid,
                    date: feeData.paymentDate,
                    description: `طالب علم کی فیس: ${feeData.monthYear}`
                });
            }
            clearFeeForm();
            loadFees();
        } catch (error) {
            console.error("Error saving fee:", error);
            showMessage('فیس درج کرنے میں خرابی۔', 'error');
        }
    }

    async function loadFees() {
        if (!db || !feesTableBody) return;

        feesTableBody.innerHTML = '<tr><td colspan="7" class="text-center">لوڈ ہو رہا ہے...</td></tr>';

        try {
            const fees = await getAllData(STORES.FEES);
            const students = await getAllData(STORES.STUDENTS);

            feesTableBody.innerHTML = fees.length === 0 ? '<tr><td colspan="7" class="text-center">کوئی فیس نہیں ملی۔</td></tr>' : '';

            let serial = 1;
            fees.forEach(fee => {
                const student = students.find(s => s.id === parseInt(fee.studentId));
                const row = feesTableBody.insertRow();
                row.innerHTML = `
                    <td>${serial++}</td>
                    <td>${student ? student.studentName : '-'}</td>
                    <td>${fee.monthYear}</td>
                    <td>${fee.amountDue}</td>
                    <td>${fee.amountPaid}</td>
                    <td>${fee.paymentDate}</td>
                    <td class="no-print">
                        <button class="edit-btn" onclick="editFee(${fee.id})">ترمیم</button>
                        <button class="delete-btn" onclick="deleteFee(${fee.id})">حذف</button>
                        <button onclick="printFeeReceipt(${fee.id})">رسید پرنٹ کریں</button>
                    </td>
                `;
            });
        } catch (error) {
            console.error("Error loading fees:", error);
            showMessage('فیس لوڈ کرنے میں خرابی۔', 'error');
        }
    }

    window.editFee = async function(id) {
        try {
            const fee = await getData(STORES.FEES, id);
            if (fee) {
                document.getElementById(FEE_FORM_FIELDS.id).value = fee.id;
                document.getElementById(FEE_FORM_FIELDS.studentId).value = fee.studentId;
                document.getElementById(FEE_FORM_FIELDS.monthYear).value = fee.monthYear;
                document.getElementById(FEE_FORM_FIELDS.amountDue).value = fee.amountDue;
                document.getElementById(FEE_FORM_FIELDS.amountPaid).value = fee.amountPaid;
                document.getElementById(FEE_FORM_FIELDS.paymentDate).value = fee.paymentDate;
                document.getElementById(FEE_FORM_FIELDS.status).value = fee.status;
                feeForm.scrollIntoView({ behavior: 'smooth' });
            }
        } catch (error) {
            console.error("Error fetching fee:", error);
            showMessage('فیس کی معلومات حاصل کرنے میں خرابی۔', 'error');
        }
    };

    window.deleteFee = async function(id) {
        if (confirm('کیا آپ واقعی اس فیس کو حذف کرنا چاہتے ہیں؟')) {
            try {
                await deleteData(STORES.FEES, id);
                showMessage('فیس حذف کر دی گئی۔', 'success');
                loadFees();
            } catch (error) {
                console.error("Error deleting fee:", error);
                showMessage('فیس حذف کرنے میں خرابی۔', 'error');
            }
        }
    };

    window.printFeeReceipt = async function(feeId) {
        try {
            const fee = await getData(STORES.FEES, feeId);
            const student = await getData(STORES.STUDENTS, parseInt(fee.studentId));
            const settings = await getData(STORES.SETTINGS, 'config');

            const receiptArea = document.createElement('div');
            receiptArea.className = 'printable-area';
            receiptArea.style.cssText = 'border: 1px solid black; padding: 20px; width: 300px; margin: 20px auto;';
            receiptArea.innerHTML = `
                <h4 style="text-align:center;">${settings?.madrasaName || 'مدرسہ'}</h4>
                <img src="${settings?.logoDataUrl || ''}" style="display: ${settings?.logoDataUrl ? 'block' : 'none'}; margin: 5px auto; max-height: 50px;">
                <p style="text-align:center;"><strong>فیس کی رسید</strong></p>
                <p><strong>نام:</strong> ${student?.studentName || '-'}</p>
                <p><strong>داخلہ نمبر:</strong> ${student?.admissionNo || '-'}</p>
                <p><strong>ماہ/سال:</strong> ${fee.monthYear}</p>
                <p><strong>رقم ادا کی:</strong> ${fee.amountPaid} PKR</p>
                <p><strong>تاریخ ادائیگی:</strong> ${fee.paymentDate}</p>
                <img src="${settings?.stampDataUrl || ''}" style="position: absolute; bottom: 5px; left: 5px; max-height: 40px; opacity: 0.7; display: ${settings?.stampDataUrl ? 'block' : 'none'};">
            `;

            document.body.appendChild(receiptArea);
            document.body.classList.add('print-receipt');
            window.print();
            document.body.classList.remove('print-receipt');
            document.body.removeChild(receiptArea);
        } catch (error) {
            console.error("Error printing fee receipt:", error);
            showMessage('رسید پرنٹ کرنے میں خرابی۔', 'error');
        }
    };

    function clearFeeForm() {
        feeForm.reset();
        document.getElementById(FEE_FORM_FIELDS.id).value = '';
    }

    document.getElementById('save-fee-btn')?.addEventListener('click', saveFee);
    document.getElementById('clear-fee-form-btn')?.addEventListener('click', clearFeeForm);
    loadFees();
    loadStudentsIntoDropdown(FEE_FORM_FIELDS.studentId);
}

function setupSalariesModule() {
    const salaryForm = document.getElementById('salary-form');
    const salariesTableBody = document.getElementById('salaries-table')?.querySelector('tbody');

    async function saveSalary() {
        if (!db || !salaryForm.checkValidity()) {
            showMessage('براہ کرم تمام ضروری خانے پُر کریں۔', 'error');
            salaryForm.reportValidity();
            return;
        }

        const salaryData = {
            teacherId: document.getElementById(SALARY_FORM_FIELDS.teacherId).value,
            monthYear: document.getElementById(SALARY_FORM_FIELDS.monthYear).value,
            amount: parseFloat(document.getElementById(SALARY_FORM_FIELDS.amount).value) || 0,
            paymentDate: document.getElementById(SALARY_FORM_FIELDS.paymentDate).value,
            status: document.getElementById(SALARY_FORM_FIELDS.status).value
        };

        const salaryId = document.getElementById(SALARY_FORM_FIELDS.id).value;

        try {
            if (salaryId) {
                salaryData.id = parseInt(salaryId);
                await updateData(STORES.SALARIES, salaryData);
                showMessage('تنخواہ اپ ڈیٹ ہو گئی۔', 'success');
            } else {
                await addData(STORES.SALARIES, salaryData);
                showMessage('تنخواہ درج کر دی گئی۔', 'success');
                // Add to ledger
                await addData(STORES.LEDGER, {
                    type: 'salary',
                    relatedId: salaryData.teacherId,
                    amount: salaryData.amount,
                    date: salaryData.paymentDate,
                    description: `استاد کی تنخواہ: ${salaryData.monthYear}`
                });
            }
            clearSalaryForm();
            loadSalaries();
        } catch (error) {
            console.error("Error saving salary:", error);
            showMessage('تنخواہ درج کرنے میں خرابی۔', 'error');
        }
    }

    async function loadSalaries() {
        if (!db || !salariesTableBody) return;

        salariesTableBody.innerHTML = '<tr><td colspan="6" class="text-center">لوڈ ہو رہا ہے...</td></tr>';

        try {
            const salaries = await getAllData(STORES.SALARIES);
            const teachers = await getAllData(STORES.TEACHERS);

            salariesTableBody.innerHTML = salaries.length === 0 ? '<tr><td colspan="6" class="text-center">کوئی تنخواہ نہیں ملی۔</td></tr>' : '';

            let serial = 1;
            salaries.forEach(salary => {
                const teacher = teachers.find(t => t.id === parseInt(salary.teacherId));
                const row = salariesTableBody.insertRow();
                row.innerHTML = `
                    <td>${serial++}</td>
                    <td>${teacher ? teacher.teacherName : '-'}</td>
                    <td>${salary.monthYear}</td>
                    <td>${salary.amount}</td>
                    <td>${salary.paymentDate}</td>
                    <td class="no-print">
                        <button class="edit-btn" onclick="editSalary(${salary.id})">ترمیم</button>
                        <button class="delete-btn" onclick="deleteSalary(${salary.id})">حذف</button>
                        <button onclick="printSalarySlip(${salary.id})">سلپ پرنٹ کریں</button>
                    </td>
                `;
            });
        } catch (error) {
            console.error("Error loading salaries:", error);
            showMessage('تنخواہیں لوڈ کرنے میں خرابی۔', 'error');
        }
    }

    window.editSalary = async function(id) {
        try {
            const salary = await getData(STORES.SALARIES, id);
            if (salary) {
                document.getElementById(SALARY_FORM_FIELDS.id).value = salary.id;
                document.getElementById(SALARY_FORM_FIELDS.teacherId).value = salary.teacherId;
                document.getElementById(SALARY_FORM_FIELDS.monthYear).value = salary.monthYear;
                document.getElementById(SALARY_FORM_FIELDS.amount).value = salary.amount;
                document.getElementById(SALARY_FORM_FIELDS.paymentDate).value = salary.paymentDate;
                document.getElementById(SALARY_FORM_FIELDS.status).value = salary.status;
                salaryForm.scrollIntoView({ behavior: 'smooth' });
            }
        } catch (error) {
            console.error("Error fetching salary:", error);
            showMessage('تنخواہ کی معلومات حاصل کرنے میں خرابی۔', 'error');
        }
    };

    window.deleteSalary = async function(id) {
        if (confirm('کیا آپ واقعی اس تنخواہ کو حذف کرنا چاہتے ہیں؟')) {
            try {
                await deleteData(STORES.SALARIES, id);
                showMessage('تنخواہ حذف کر دی گئی۔', 'success');
                loadSalaries();
            } catch (error) {
                console.error("Error deleting salary:", error);
                showMessage('تنخواہ حذف کرنے میں خرابی۔', 'error');
            }
        }
    };

    window.printSalarySlip = async function(salaryId) {
        try {
            const salary = await getData(STORES.SALARIES, salaryId);
            const teacher = await getData(STORES.TEACHERS, parseInt(salary.teacherId));
            const settings = await getData(STORES.SETTINGS, 'config');

            const slipArea = document.createElement('div');
            slipArea.className = 'printable-area';
            slipArea.style.cssText = 'border: 1px solid black; padding: 20px; width: 300px; margin: 20px auto;';
            slipArea.innerHTML = `
                <h4 style="text-align:center;">${settings?.madrasaName || 'مدرسہ'}</h4>
                <img src="${settings?.logoDataUrl || ''}" style="display: ${settings?.logoDataUrl ? 'block' : 'none'}; margin: 5px auto; max-height: 50px;">
                <p style="text-align:center;"><strong>تنخواہ کی سلپ</strong></p>
                <p><strong>نام:</strong> ${teacher?.teacherName || '-'}</p>
                <p><strong>ماہ/سال:</strong> ${salary.monthYear}</p>
                <p><strong>رقم:</strong> ${salary.amount} PKR</p>
                <p><strong>تاریخ ادائیگی:</strong> ${salary.paymentDate}</p>
                <img src="${settings?.stampDataUrl || ''}" style="position: absolute; bottom: 5px; left: 5px; max-height: 40px; opacity: 0.7; display: ${settings?.stampDataUrl ? 'block' : 'none'};">
            `;

            document.body.appendChild(slipArea);
            document.body.classList.add('print-slip');
            window.print();
            document.body.classList.remove('print-slip');
            document.body.removeChild(slipArea);
        } catch (error) {
            console.error("Error printing salary slip:", error);
            showMessage('سلپ پرنٹ کرنے میں خرابی۔', 'error');
        }
    };

    function clearSalaryForm() {
        salaryForm.reset();
        document.getElementById(SALARY_FORM_FIELDS.id).value = '';
    }

    document.getElementById('save-salary-btn')?.addEventListener('click', saveSalary);
    document.getElementById('clear-salary-form-btn')?.addEventListener('click', clearSalaryForm);
    loadSalaries();
    loadTeachersIntoDropdown(SALARY_FORM_FIELDS.teacherId);
}

function setupLedgerModule() {
    const ledgerForm = document.getElementById('ledger-form');
    const ledgerTableBody = document.getElementById('ledger-table')?.querySelector('tbody');

    async function saveLedger() {
        if (!db || !ledgerForm.checkValidity()) {
            showMessage('براہ کرم تمام ضروری خانے پُر کریں۔', 'error');
            ledgerForm.reportValidity();
            return;
        }

        const ledgerData = {
            type: document.getElementById(LEDGER_FORM_FIELDS.type).value,
            relatedId: document.getElementById(LEDGER_FORM_FIELDS.relatedId).value || null,
            amount: parseFloat(document.getElementById(LEDGER_FORM_FIELDS.amount).value) || 0,
            date: document.getElementById(LEDGER_FORM_FIELDS.date).value,
            description: document.getElementById(LEDGER_FORM_FIELDS.description).value.trim()
        };

        const ledgerId = document.getElementById(LEDGER_FORM_FIELDS.id).value;

        try {
            if (ledgerId) {
                ledgerData.id = parseInt(ledgerId);
                await updateData(STORES.LEDGER, ledgerData);
                showMessage('لیجر اندراج اپ ڈیٹ ہو گیا۔', 'success');
            } else {
                await addData(STORES.LEDGER, ledgerData);
                showMessage('نیا لیجر اندراج درج کر دیا گیا۔', 'success');
            }
            clearLedgerForm();
            loadLedger();
        } catch (error) {
            console.error("Error saving ledger:", error);
            showMessage('لیجر اندراج درج کرنے میں خرابی۔', 'error');
        }
    }

    async function loadLedger() {
        if (!db || !ledgerTableBody) return;

        ledgerTableBody.innerHTML = '<tr><td colspan="6" class="text-center">لوڈ ہو رہا ہے...</td></tr>';

        try {
            const ledger = await getAllData(STORES.LEDGER);
            const students = await getAllData(STORES.STUDENTS);
            const teachers = await getAllData(STORES.TEACHERS);

            ledgerTableBody.innerHTML = ledger.length === 0 ? '<tr><td colspan="6" class="text-center">کوئی لیجر اندراج نہیں ملا۔</td></tr>' : '';

            let serial = 1;
            ledger.forEach(entry => {
                let relatedName = '-';
                if (entry.type === 'fee' && entry.relatedId) {
                    const student = students.find(s => s.id === parseInt(entry.relatedId));
                    relatedName = student ? student.studentName : '-';
                } else if (entry.type === 'salary' && entry.relatedId) {
                    const teacher = teachers.find(t => t.id === parseInt(entry.relatedId));
                    relatedName = teacher ? teacher.teacherName : '-';
                }
                const row = ledgerTableBody.insertRow();
                row.innerHTML = `
                    <td>${serial++}</td>
                    <td>${entry.type}</td>
                    <td>${relatedName}</td>
                    <td>${entry.amount}</td>
                    <td>${entry.date}</td>
                    <td class="no-print">
                        <button class="edit-btn" onclick="editLedger(${entry.id})">ترمیم</button>
                        <button class="delete-btn" onclick="deleteLedger(${entry.id})">حذف</button>
                    </td>
                `;
            });
        } catch (error) {
            console.error("Error loading ledger:", error);
            showMessage('لیجر لوڈ کرنے میں خرابی۔', 'error');
        }
    }

    window.editLedger = async function(id) {
        try {
            const entry = await getData(STORES.LEDGER, id);
            if (entry) {
                document.getElementById(LEDGER_FORM_FIELDS.id).value = entry.id;
                document.getElementById(LEDGER_FORM_FIELDS.type).value = entry.type;
                document.getElementById(LEDGER_FORM_FIELDS.relatedId).value = entry.relatedId;
                document.getElementById(LEDGER_FORM_FIELDS.amount).value = entry.amount;
                document.getElementById(LEDGER_FORM_FIELDS.date).value = entry.date;
                document.getElementById(LEDGER_FORM_FIELDS.description).value = entry.description;
                ledgerForm.scrollIntoView({ behavior: 'smooth' });
            }
        } catch (error) {
            console.error("Error fetching ledger:", error);
            showMessage('لیجر کی معلومات حاصل کرنے میں خرابی۔', 'error');
        }
    };

    window.deleteLedger = async function(id) {
        if (confirm('کیا آپ واقعی اس لیجر اندراج کو حذف کرنا چاہتے ہیں؟')) {
            try {
                await deleteData(STORES.LEDGER, id);
                showMessage('لیجر اندراج حذف کر دیا گیا۔', 'success');
                loadLedger();
            } catch (error) {
                console.error("Error deleting ledger:", error);
                showMessage('لیجر اندراج حذف کرنے میں خرابی۔', 'error');
            }
        }
    };

    function clearLedgerForm() {
        ledgerForm.reset();
        document.getElementById(LEDGER_FORM_FIELDS.id).value = '';
    }

    document.getElementById('save-ledger-btn')?.addEventListener('click', saveLedger);
    document.getElementById('clear-ledger-form-btn')?.addEventListener('click', clearLedgerForm);
    loadLedger();
}

function setupReportsModule() {
    async function generateReport(type) {
        if (!db) return;

        try {
            let data, reportTableBody;
            switch (type) {
                case 'students':
                    reportTableBody = document.getElementById('students-report-table')?.querySelector('tbody');
                    if (!reportTableBody) return;
                    data = await getAllData(STORES.STUDENTS);
                    reportTableBody.innerHTML = data.length === 0 ? '<tr><td colspan="5" class="text-center">کوئی طالب علم نہیں ملا۔</td></tr>' : '';
                    let serial = 1;
                    data.forEach(student => {
                        const row = reportTableBody.insertRow();
                        row.innerHTML = `
                            <td>${serial++}</td>
                            <td>${student.studentName}</td>
                            <td>${student.fatherName}</td>
                            <td>${student.studentClass}</td>
                            <td>${translateStatus(student.studentStatus)}</td>
                        `;
                    });
                    break;
                case 'fees':
                    reportTableBody = document.getElementById('fees-report-table')?.querySelector('tbody');
                    if (!reportTableBody) return;
                    data = await getAllData(STORES.FEES);
                    const students = await getAllData(STORES.STUDENTS);
                    reportTableBody.innerHTML = data.length === 0 ? '<tr><td colspan="6" class="text-center">کوئی فیس نہیں ملی۔</td></tr>' : '';
                    serial = 1;
                    data.forEach(fee => {
                        const student = students.find(s => s.id === parseInt(fee.studentId));
                        const row = reportTableBody.insertRow();
                        row.innerHTML = `
                            <td>${serial++}</td>
                            <td>${student ? student.studentName : '-'}</td>
                            <td>${fee.monthYear}</td>
                            <td>${fee.amountDue}</td>
                            <td>${fee.amountPaid}</td>
                            <td>${fee.paymentDate}</td>
                        `;
                    });
                    break;
                case 'salaries':
                    reportTableBody = document.getElementById('salaries-report-table')?.querySelector('tbody');
                    if (!reportTableBody) return;
                    data = await getAllData(STORES.SALARIES);
                    const teachers = await getAllData(STORES.TEACHERS);
                    reportTableBody.innerHTML = data.length === 0 ? '<tr><td colspan="5" class="text-center">کوئی تنخواہ نہیں ملی۔</td></tr>' : '';
                    serial = 1;
                    data.forEach(salary => {
                        const teacher = teachers.find(t => t.id === parseInt(salary.teacherId));
                        const row = reportTableBody.insertRow();
                        row.innerHTML = `
                            <td>${serial++}</td>
                            <td>${teacher ? teacher.teacherName : '-'}</td>
                            <td>${salary.monthYear}</td>
                            <td>${salary.amount}</td>
                            <td>${salary.paymentDate}</td>
                        `;
                    });
                    break;
                case 'attendance':
                    reportTableBody = document.getElementById('attendance-report-table')?.querySelector('tbody');
                    if (!reportTableBody) return;
                    data = await getAllData(STORES.ATTENDANCE_STUDENTS);
                    const attStudents = await getAllData(STORES.STUDENTS);
                    const attClasses = await getAllData(STORES.CLASSES);
                    reportTableBody.innerHTML = data.length === 0 ? '<tr><td colspan="5" class="text-center">کوئی حاضری نہیں ملی۔</td></tr>' : '';
                    serial = 1;
                    data.forEach(att => {
                        const student = attStudents.find(s => s.id === parseInt(att.studentId));
                        const cls = attClasses.find(c => c.id === parseInt(att.classId));
                        const row = reportTableBody.insertRow();
                        row.innerHTML = `
                            <td>${serial++}</td>
                            <td>${student ? student.studentName : '-'}</td>
                            <td>${cls ? cls.className : '-'}</td>
                            <td>${att.date}</td>
                            <td>${att.status}</td>
                        `;
                    });
                    break;
            }
            showMessage(`${type} رپورٹ تیار ہو گئی۔`, 'success');
            document.body.classList.add(`print-${type}-report`);
            window.print();
            document.body.classList.remove(`print-${type}-report`);
        } catch (error) {
            console.error(`Error generating ${type} report:`, error);
            showMessage(`${type} رپورٹ تیار کرنے میں خرابی۔`, 'error');
        }
    }

    document.getElementById('generate-students-report-btn')?.addEventListener('click', () => generateReport('students'));
    document.getElementById('generate-fees-report-btn')?.addEventListener('click', () => generateReport('fees'));
    document.getElementById('generate-salaries-report-btn')?.addEventListener('click', () => generateReport('salaries'));
    document.getElementById('generate-attendance-report-btn')?.addEventListener('click', () => generateReport('attendance'));
}

function loadModuleData(moduleName) {
    console.log(`Loading data for: ${moduleName}`);
    switch (moduleName) {
        case 'dashboard':
            setupDashboardModule();
            break;
        case 'students':
            loadClassesIntoDropdown('student-class');
            loadClassesIntoDropdown('filter-student-class');
            loadStudents();
            break;
        case 'settings':
            loadSettings();
            break;
        case 'teachers':
            setupTeacherModule();
            break;
        case 'classes':
            setupClassesModule();
            break;
        case 'attendance':
            setupAttendanceModule();
            break;
        case 'exams':
            setupExamsModule();
            break;
        case 'fees':
            setupFeesModule();
            break;
        case 'salaries':
            setupSalariesModule();
            break;
        case 'ledger':
            setupLedgerModule();
            break;
        case 'reports':
            setupReportsModule();
            break;
    }
}

// Override the original initialization to include all modules
document.addEventListener('DOMContentLoaded', async () => {
    try {
        await openDatabase();
        setupNavigation();
        loadModuleData('dashboard');
        loadSettings();
        await loadClassesIntoDropdown('student-class');
        await loadClassesIntoDropdown('filter-student-class');
        await loadTeachersIntoDropdown(SUBJECT_FORM_FIELDS.teacherId);
        await loadTeachersIntoDropdown(SALARY_FORM_FIELDS.teacherId);
        await loadStudentsIntoDropdown(FEE_FORM_FIELDS.studentId);
        await loadStudentsIntoDropdown(ATTENDANCE_FORM_FIELDS.studentId);
        await loadStudentsIntoDropdown(MARKS_FORM_FIELDS.studentId);
        await loadExamsIntoDropdown(MARKS_FORM_FIELDS.examId);
        await loadSubjectsIntoDropdown(MARKS_FORM_FIELDS.subjectId);
    } catch (error) {
        console.error("Initialization failed:", error);
        showMessage('ایپلیکیشن شروع کرنے میں ناکامی۔ تفصیلات کے لیے کنسول چیک کریں۔', 'error');
    }
});

