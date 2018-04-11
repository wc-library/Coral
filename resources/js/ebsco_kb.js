$(document).ready(function(){

  updateSearch(1, updateSearchForm);

  //bind change event to each of the page start
  $(".setPage").live('click', function (e) {
    e.preventDefault();
    updateSearch($(this).data('page'));
  });

  $(".setVendor").live('click', function (e) {
    e.preventDefault();
    resetSearch(
        setVendorLimit.bind(
            null,
            $(this).data('vendor-id'),
            $(this).data('vendor-name'),
            updateSearch.bind(
                null,
                1,
                updateSearchForm
            )
        )
    );
  });

  $(".setPackage").live('click', function (e) {
    e.preventDefault();
    resetSearch(
        setPackageLimit.bind(
            null,
            $(this).data('vendor-id'),
            $(this).data('package-id'),
            $(this).data('package-name'),
            updateSearch.bind(
                null,
                1,
                updateSearchForm
            )
        )
    );
  });

  $('#selectType').change(function(){
    $('#searchType').val($(this).val());
    resetSearch(updateSearchForm.bind(null, 1));
  });

  $(".searchButton").click(function(e) {
    e.preventDefault();
    $('#ebscoKbSearchForm').submit();
  });

  $('#ebscoKbSearchForm').submit(function(e){
    e.preventDefault();
    updateSearch(1);
  });

  $(".newSearch").click(function () {
    resetSearch(updateSearchForm.bind(null, 1));
  });

  $("#removeLimit").click(function() {
    resetSearch(updateSearch.bind(null, 1, updateSearchForm));
  });

  $('#showAllPackages').live('change', function() {
    if($(this).is(':checked')){
      $('.packageOption').show();
    } else {
      $('.packageOption').hide();
      $('.selectedPackage').show();
    }
  });

});

function resetSearch(callback) {
  //reset fields
  $('#ebscoKbSearchForm :input').not('#selectType, #searchType').each(function(){
    $(this).val($(this).data('default'));
  });

  if (typeof(callback) == 'function') {
    callback();
  }
}

function updateSearch(page, callback) {
  $("#div_feedback").html("<img src='images/circle.gif'>  <span style='font-size:90%'>"+_("Processing...")+"</span>");
  $("#div_searchResults").html("");
  $('#searchOffset').val(page)
  var form = $('#ebscoKbSearchForm');
  $.post(
      form.attr('action'),
      form.serialize(),
      function(html) {
        $("#div_feedback").html("&nbsp;");
        $("#div_searchResults").html(html);
        tb_reinit();
      }
  );
  if (typeof(callback) == 'function') {
    callback();
  }
  window.scrollTo(0, 0);
}

function updateSearchForm() {
  $('.ebsco-toggle-option').hide();
  var selected = $('#searchType').val();
  $('.'+selected+'-option').show();

  if($('#searchVendorId').val() || $('#searchPackageId').val()){
    $('#limitBy').show()
  } else {
    $('#limitBy').hide()
  }
}

function setVendorLimit(vendorId, vendorName, callback) {

  $('#selectType').val('packages');
  $('#searchType').val('packages');
  $('#searchVendorId').val(vendorId);
  $('#limitName').html(vendorName);
  $('#limitBy label').html('from vendor');

  if (typeof(callback) == 'function') {
    callback();
  }
}

function setPackageLimit(vendorId, packageId, packageName, callback) {

  $('#selectType').val('titles')
  $('#searchType').val('titles');
  $('#searchVendorId').val(vendorId);
  $('#searchPackageId').val(packageId);
  $('#limitName').html(packageName);
  $('#limitBy label').html('from package');

  if (typeof(callback) == 'function') {
    callback();
  }
}


function setNumberOfRecords(recordsPerPageNumber){
  $("#searchRecordsPerPage").val(recordsPerPageNumber);
  updateSearch();
}

