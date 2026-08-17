import './bootstrap';
import { createApp } from 'vue';
import AdminShell from './components/AdminShell.vue';
import AdminDashboard from './components/AdminDashboard.vue';
import AdminDataTable from './components/AdminDataTable.vue';
import CourseUploadForm from './components/CourseUploadForm.vue';

const adminRoot = document.getElementById('admin-app');

if (adminRoot) {
    const app = createApp({});

    app.component('admin-shell', AdminShell);
    app.component('admin-dashboard', AdminDashboard);
    app.component('admin-data-table', AdminDataTable);
    app.component('course-upload-form', CourseUploadForm);
    app.mount(adminRoot);
}
