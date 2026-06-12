// Bootstrap 5
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// AdminLTE 4 (ES module, no jQuery required)
import 'admin-lte/dist/js/adminlte.min.js';

// jQuery (still needed for DataTables & legacy helpers)
import $ from 'jquery';
window.$ = window.jQuery = $;

// DataTables + Bootstrap 5 theme
import DataTable from 'datatables.net-bs5';
window.DataTable = DataTable;

// SweetAlert2
import Swal from 'sweetalert2';
window.Swal = Swal;

// Chart.js
import Chart from 'chart.js/auto';
window.Chart = Chart;
