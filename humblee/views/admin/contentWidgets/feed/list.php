<h2 class="title">Feed Articles</h2>

<a href="/admin/edit/96/" class="button has-text-info">
    Create New &nbsp;
    <i class="fas fa-edit"></i>
</a>

<div class="dataTableWrapper" style="padding-top: 20px; width: 100%">
    <table class="table" style="width: 100%"></table>
</div>

<style type="text/css">
    table tbody tr {
        cursor: pointer;
    }

    table tbody tr:hover {
        background-color: cornsilk;
    }
</style>

<script type="text/javascript">
    (async () => {
        const data = await fetch("/core-request/feed/list/", {
                headers: {
                    'content-type': 'application/json;charset=UTF-8',
                }
            })
            .then((response) => {
                if (response.ok) {
                    let fetchedData = response.json()
                    return fetchedData
                } else {
                    return new Error("Invalid Response")
                }
            })
            .catch((error) => {
                console.error(error)
                return new Error(error)
            })

        loadDataTable(data)
    })();

    function loadDataTable(dataSet) {
        $('table').DataTable({
            data: dataSet,
            columns: [{
                    data: "display_date",
                    title: "Release Date <span class=\"icon tooltip has-text-info\" data-tooltip=\"Date Article Appears in Feed\"><i class=\"fas fa-info-circle\"></i></span>",
                    render: (data, type, row) => {
                        let dateTimeParts = data.split(/[- :]/); // regular expression split that creates array with: year, month, day, hour, minutes, seconds values
                        dateTimeParts[1]--; // monthIndex begins with 0 for January and ends with 11 for December so we need to decrement by one
                        if (data == null || data == "0000-00-00 00:00:00") {
                            return '<i class="fas fa-ban"></i> Unpublished';
                        }
                        let icon_class = "is-hidden";
                        let icon_text = "";
                        let displayDate = Date.parse(data);
                        let now = Date.now();
                        let endDate = (row["end_date"] != null && row["end_date"] != "0000-00-00 00:00:00") ? Date.parse(row["end_date"]) : false;

                        if (displayDate > now) {
                            icon_class = "has-text-success";
                            icon_text = "Scheduled Future Release Date";
                        }
                        if (displayDate < now && endDate !== false && endDate > now) {
                            icon_class = "has-text-warning";
                            icon_text = "Scheduled to Expire at " + row["end_date"];
                        }
                        if (endDate !== false && endDate < now) {
                            icon_class = "has-text-danger";
                            icon_text = "Expired and Archived at " + row["end_date"];
                        }
                        return (displayDate > now || endDate !== false) ? data + '<span class="icon tooltip" data-tooltip="' + icon_text + '"><i class="fas fa-clock ' + icon_class + '"></i></span>' : data;
                    },
                },

                {
                    data: "headline",
                    title: "Headline"
                }, {
                    data: "publish_date",
                    title: "Publish Date",
                    render: (data, type, row) => {
                        return (data == null || data == "0000-00-00 00:00:00") ? '<i class="fas fa-tasks"></i> Draft' : data
                    },
                }, {
                    data: "revision_date",
                    title: "Revision Date",
                    render: (data, type, row) => {
                        return '<span class="tooltip" data-tooltip="ID: ' + row["id"] + '">' + data + '</span>';
                    }
                }, {
                    data: "author",
                    title: "Revision Author"
                }, {
                    data: "template",
                    title: "Template",
                    render: (data, type, row) => {
                        switch (data) {
                            case 'cta':
                                return "Call to Action";
                                break;
                            case 'profile':
                                return "Profile";
                                break;
                            case 'highlight':
                                return "Quick Highlight";
                                break;
                            default:
                                return "Default"

                        }
                    }
                }
            ],
            order: [
                [0, "desc"],
                [2, "desc"]
            ],
            pageLength: 25,
            fixedHeader: true,
            responsive: true,
            createdRow: function(row, data, dataIndex) {
                $(row).attr('data-articleid', data.id);
            },
            fnDrawCallback: function(oSettings) {
                $('.dataTableWrapper select').addClass('select'); //.wrap("<div class='select'></div>");
                $('.dataTableWrapper input[type=search]').addClass("input").css({
                    width: '150px'
                }).focus();
                $('.dataTableWrapper .dataTables_length').css({
                    'line-height': '1.7rem'
                });
                $('.dataTableWrapper .dataTables_filter').css({
                    'line-height': '2em'
                });
            }
        });
        $('table tbody').on("click", "tr", function(el) {
            let articleID = $(this).data('articleid');
            window.location = '/admin/edit/96/#' + articleID;
        });
    }
</script>