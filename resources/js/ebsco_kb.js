$(document).ready(function(){

  updateSearch($('#searchOffset').val());

  //bind change event to each of the page start
  $(".setPage").live('click', function (e) {
    e.preventDefault();
    console.log($(this).data('page'));
    updateSearch($(this).data('page'));
  });

  $('#searchType').change(function(){
    updateSearchForm();
    resetSearch();
  });

  $(".searchButton").click(function() {
    $('#ebscoKbSearchForm').submit();
    return false;
  });

  $('#ebscoKbSearchForm').submit(function(e){
    e.preventDefault();
    updateSearch($('input[name="search[offset]"]').val());
  });

  $(".newSearch").click(function () {
    resetSearch();
  });

});

function resetSearch() {
  //reset fields
  $('#ebscoKbSearchForm :input').not('#searchType').each(function(){
    $(this).val($(this).data('default'));
  });
}

function updateSearch(pageNumber) {
  $("#div_feedback").html("<img src='images/circle.gif'>  <span style='font-size:90%'>"+_("Processing...")+"</span>");
  $("#div_searchResults").html("");
  console.log(pageNumber);
  if (!pageNumber) {
    pageNumber = 1;
  }
  $('input[name="search[offset]"]').val(pageNumber);

  var form = $('#ebscoKbSearchForm');
  $.post(
      form.attr('action'),
      form.serialize(),
      function(html) {
        $("#div_feedback").html("&nbsp;");
        $("#div_searchResults").html(html);
      }
  );

  updateSearchForm();

  window.scrollTo(0, 0);
}

function updateSearchForm() {
  $('.ebsco-toggle-option').hide();
  var selected = $('#searchType').val();
  $('.'+selected+'-option').show();
}


function setNumberOfRecords(recordsPerPageNumber){
  $("#searchRecordsPerPage").val(recordsPerPageNumber);
  updateSearch();
}

