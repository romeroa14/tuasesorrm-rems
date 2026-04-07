// Formatting function for row details - modify as you need
function format(d) {
    // `d` is the original data object for the row
    return (
        '<dl>' +
            '<dt>Link de wasi sin datos de RM:</dt>' +
            '<dd>' +
                '<a href="' + d.link_wasi_no_data +'" target="_blank">' +
                d.link_wasi_no_data +
                '</a>' +
            '</dd>' +
            '<hr>' +
            '<dt>Link de wasi con datos de RM:</dt>' +
            '<dd>' +
                '<a href="' + d.link_wasi_with_data +'" target="_blank">' +
                d.link_wasi_with_data +
                '</a>' +
            '</dd>' +
        '</dl>'
    );
}

let table = new DataTable('#table_wasi', {
    "ajax": getWasiAll,
    columns: [
        {
            className: 'dt-control',
            orderable: false,
            data: null,
            defaultContent: ''
        },
        { data: 'id_property' },
        { data: 'code_wasi' },
        { data: 'created_at' }
    ],
    order: [[1, 'asc']]
});

// Add event listener for opening and closing details
table.on('click', 'td.dt-control', function (e) {
    let tr = e.target.closest('tr');
    let row = table.row(tr);

    if (row.child.isShown()) {
        // This row is already open - close it
        row.child.hide();
    }
    else {
        // Open this row
        row.child(format(row.data())).show();
    }
});