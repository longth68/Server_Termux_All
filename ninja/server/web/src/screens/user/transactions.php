<div class="table-responsive">
  <table
    class="table align-middle table-row-dashed gy-5 dataTable no-footer"
    role="table"
  >
    <thead>
      <tr class="text-start fw-bold text-uppercase gs-0">
        <th
          colspan="1"
          role="columnheader"
          class="table-sort-desc text-primary"
          style="cursor: pointer"
        >
          #ID
        </th>
        <th colspan="1" role="columnheader" class="" style="cursor: pointer">
          Số tiền
        </th>
         <th colspan="1" role="columnheader" class="" style="cursor: default">
          Trước G.D
        </th>
        <th colspan="1" role="columnheader" class="" style="cursor: default">
          Sau G.D
        </th>
        <th colspan="1" role="columnheader" class="" style="cursor: default">
          Mô tả
        </th>
        <th colspan="1" role="columnheader" class="" style="cursor: pointer">
          Ngày tạo
        </th>
      </tr>
    </thead>
    <tbody class="fw-semibold" role="rowgroup" id="list-transaction">
      <tr id="noContent">
        <td colspan="12">
          <div
            class="d-flex text-center w-100 align-content-center justify-content-center"
          >
            Không có bản ghi nào
          </div>
        </td>
      </tr>
    </tbody>
  </table>
</div>
<div class="row">
  <div
    class="col-sm-12 col-md-5 d-flex align-items-center justify-content-center justify-content-md-start"
  ></div>
  <div
    class="col-sm-12 col-md-7 d-flex align-items-center justify-content-center justify-content-md-end"
  >
    <div>
      <ul class="pagination">
        <li class="page-item">
          <a class="page-link" style="cursor: pointer">&lt;</a>
        </li>
        <li class="page-item active">
          <a class="page-link" style="cursor: pointer">1</a>
        </li>
        <li class="page-item">
          <a class="page-link" style="cursor: pointer">&gt;</a>
        </li>
      </ul>
    </div>
  </div>
</div>
<script>
  let data;
  $.ajax({
    url: "/apixuli/getListTransaction",
    type: "GET",
    dataType: "json",
    success: function (data) {
      $("#noContent").hide();
      let itemHTML = "";
      if (data.code != "01" && data.length > 0) {
        this.data = data;
        data.forEach((item, index) => {
          itemHTML += `<tr class="content-row"><td>` + (index + 1) + `</td>`;
          itemHTML += `<td>` + item[0] + `</td>`;
          itemHTML +=`<td>` + item[1] + `</td>`;
           itemHTML +=`<td>` + item[2] + `</td>`;
          itemHTML += `<td>` + (item[3] == null ? "" : item[3]) + `</td>`;
          itemHTML += `<td>` + item[4] + `</td></tr>`;
        });

        $("#list-transaction").append(itemHTML);
        $("#list-transaction").show();
        pageSize = 12;
        pagesCount = $(".content-row").length;
        var totalPages = Math.ceil(pagesCount / pageSize);

        $(".pagination").twbsPagination({
          totalPages: totalPages,
          visiblePages: 5,
          prev: "&lt;",
          next: "&gt;",
          first: "",
          onPageClick: function (event, page) {
            var startIndex = pageSize * (page - 1);
            var endIndex = startIndex + pageSize;
            $(".content-row")
              .hide()
              .filter(function () {
                var idx = $(this).index();
                return idx >= startIndex && idx < endIndex;
              })
              .show();
          },
        });
      } else {
      $("#noContent").css("display", "contents");
        $("#noContent").css("vertical-align", "middle");
        $("#noContent").css("    text-align", "center");
      }
    },
    error: function (xhr, textStatus, errorThrown) {
      $("#overlay").hide();
      console.log("Error in Operation", errorThrown);
    },
  });
  
</script>
