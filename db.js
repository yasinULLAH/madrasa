const DB_NAME = 'MadrasaDB';
const DB_VERSION = 1;

const STORES = {
    STUDENTS: 'students',
    TEACHERS: 'teachers',
    CLASSES: 'classes',
    SUBJECTS: 'subjects',
    ATTENDANCE_STUDENTS: 'attendance_students',
    EXAMS: 'exams',
    MARKS: 'marks',
    FEES: 'fees',
    SALARIES: 'salaries',
    LEDGER: 'ledger',
    SETTINGS: 'settings'
};

let db;

async function openDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            db = request.result;
            resolve(db);
        };

        request.onupgradeneeded = (event) => {
            const db = event.target.result;

            if (!db.objectStoreNames.contains(STORES.STUDENTS)) {
                const studentStore = db.createObjectStore(STORES.STUDENTS, { keyPath: 'id', autoIncrement: true });
                studentStore.createIndex('admissionNo', 'admissionNo', { unique: true });
                studentStore.createIndex('phone', 'studentPhone', { unique: false });
            }

            if (!db.objectStoreNames.contains(STORES.TEACHERS)) {
                const teacherStore = db.createObjectStore(STORES.TEACHERS, { keyPath: 'id', autoIncrement: true });
                teacherStore.createIndex('phone', 'teacherPhone', { unique: true });
            }

            if (!db.objectStoreNames.contains(STORES.CLASSES)) {
                const classStore = db.createObjectStore(STORES.CLASSES, { keyPath: 'id', autoIncrement: true });
                classStore.createIndex('name', 'className', { unique: true });
            }

            if (!db.objectStoreNames.contains(STORES.SUBJECTS)) {
                db.createObjectStore(STORES.SUBJECTS, { keyPath: 'id', autoIncrement: true });
            }

            if (!db.objectStoreNames.contains(STORES.ATTENDANCE_STUDENTS)) {
                const attendanceStore = db.createObjectStore(STORES.ATTENDANCE_STUDENTS, { keyPath: 'id', autoIncrement: true });
                attendanceStore.createIndex('date_student', ['date', 'studentId'], { unique: true });
            }

            if (!db.objectStoreNames.contains(STORES.EXAMS)) {
                db.createObjectStore(STORES.EXAMS, { keyPath: 'id', autoIncrement: true });
            }

            if (!db.objectStoreNames.contains(STORES.MARKS)) {
                const marksStore = db.createObjectStore(STORES.MARKS, { keyPath: 'id', autoIncrement: true });
                marksStore.createIndex('exam_student_subject', ['examId', 'studentId', 'subjectId'], { unique: true });
            }

            if (!db.objectStoreNames.contains(STORES.FEES)) {
                db.createObjectStore(STORES.FEES, { keyPath: 'id', autoIncrement: true });
            }

            if (!db.objectStoreNames.contains(STORES.SALARIES)) {
                db.createObjectStore(STORES.SALARIES, { keyPath: 'id', autoIncrement: true });
            }

            if (!db.objectStoreNames.contains(STORES.LEDGER)) {
                db.createObjectStore(STORES.LEDGER, { keyPath: 'id', autoIncrement: true });
            }

            if (!db.objectStoreNames.contains(STORES.SETTINGS)) {
                db.createObjectStore(STORES.SETTINGS, { keyPath: 'id' });
            }
        };
    });
}

async function addData(storeName, data) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);
        const request = store.add(data);

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function getData(storeName, id) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction([storeName], 'readonly');
        const store = transaction.objectStore(storeName);
        const request = store.get(id);

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function getAllData(storeName, indexName, indexValue) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction([storeName], 'readonly');
        const store = transaction.objectStore(storeName);
        let request;

        if (indexName && indexValue) {
            let index;
            if (Array.isArray(indexValue)) {
                index = store.index(indexName);
                request = index.get(indexValue);
            } else {
                index = store.index(indexName);
                request = index.getAll(indexValue);
            }
        } else {
            request = store.getAll();
        }

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function updateData(storeName, data) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);
        const request = store.put(data);

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function deleteData(storeName, id) {
    return new Promise((resolve, reject) => {
        const transaction = db.transaction([storeName], 'readwrite');
        const store = transaction.objectStore(storeName);
        const request = store.delete(id);

        request.onsuccess = () => resolve();
        request.onerror = () => reject(request.error);
    });
}