$(document).ready(function(){

  updateSearch(updateSearchForm);

  //bind change event to each of the page start
  $(".setPage").live('click', function (e) {
    console.log('page set');
    e.preventDefault();
    $('#searchOffset').val($(this).data('page'));
    updateSearch();
  });

  $(".setVendor").live('click', function (e) {
    console.log('vendor set');
    e.preventDefault();
    resetSearch(
        setVendorLimit.bind(
            null,
            $(this).data('vendor-id'),
            $(this).data('vendor-name'),
            updateSearch.bind(
                null,
                updateSearchForm
            )
        )
    );
  });

  $(".setPackage").live('click', function (e) {
    console.log('package set');
    e.preventDefault();
    resetSearch(
        setPackageLimit.bind(
            null,
            $(this).data('vendor-id'),
            $(this).data('package-id'),
            $(this).data('package-name'),
            updateSearch.bind(
                null,
                updateSearchForm
            )
        )
    );
  });

  $('#selectType').change(function(){
    console.log('select changed');
    $('#searchType').val($(this).val());
    resetSearch(updateSearchForm);
  });

  $(".searchButton").click(function(e) {
    console.log('search button hit');
    e.preventDefault();
    $('#ebscoKbSearchForm').submit();
  });

  $('#ebscoKbSearchForm').submit(function(e){
    console.log('submitting');
    e.preventDefault();
    updateSearch();
  });

  $(".newSearch").click(function () {
    console.log('new search hit');
    resetSearch(updateSearchForm);
  });

  $("#removeLimit").click(function() {
    console.log('remove vendor hit');
    resetSearch(updateSearch.bind(null, updateSearchForm));
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
  console.log('resetting search');
  $('#ebscoKbSearchForm :input').not('#selectType, #searchType').each(function(){
    $(this).val($(this).data('default'));
  });

  console.log(typeof(callback));
  if (typeof(callback) == 'function') {
    console.log('resetting search complete');
    callback();
  }
}

function updateSearch(callback) {
  console.log('running search');
  $("#div_feedback").html("<img src='images/circle.gif'>  <span style='font-size:90%'>"+_("Processing...")+"</span>");
  $("#div_searchResults").html("");
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
  console.log(typeof(callback));
  if (typeof(callback) == 'function') {
    console.log('running search complete');
    callback();
  }
  window.scrollTo(0, 0);
}

function updateSearchForm() {
  console.log('updating search form');
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
  console.log('setting vendor option');

  $('#selectType').val('packages');
  $('#searchType').val('packages');
  $('#searchVendorId').val(vendorId);
  $('#limitName').html(vendorName);
  $('#limitBy label').html('from vendor');

  if (typeof(callback) == 'function') {
    console.log('setting vendor option complete');
    callback();
  }
}

function setPackageLimit(vendorId, packageId, packageName, callback) {
  console.log('setting vendor option');

  $('#selectType').val('titles')
  $('#searchType').val('titles');
  $('#searchVendorId').val(vendorId);
  $('#searchPackageId').val(packageId);
  $('#limitName').html(packageName);
  $('#limitBy label').html('from package');

  if (typeof(callback) == 'function') {
    console.log('setting vendor option complete');
    callback();
  }
}


function setNumberOfRecords(recordsPerPageNumber){
  $("#searchRecordsPerPage").val(recordsPerPageNumber);
  updateSearch();
}

