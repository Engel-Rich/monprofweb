import './bootstrap';
import { createApp } from 'vue';
import AdminShell from './components/AdminShell.vue';
import AdminDashboard from './components/AdminDashboard.vue';
import AdminDataTable from './components/AdminDataTable.vue';
import AdminStatistics from './components/AdminStatistics.vue';
import CourseUploadForm from './components/CourseUploadForm.vue';
import AdminActionButtons from './components/AdminActionButtons.vue';

const adminRoot = document.getElementById('admin-app');

if (adminRoot) {
    const app = createApp({});

    app.component('admin-shell', AdminShell);
    app.component('admin-dashboard', AdminDashboard);
    app.component('admin-data-table', AdminDataTable);
    app.component('admin-statistics', AdminStatistics);
    app.component('course-upload-form', CourseUploadForm);
    app.component('admin-action-buttons', AdminActionButtons);
    app.mount(adminRoot);
}
