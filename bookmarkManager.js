
function deleteEntry(id)
{
	$.post('', { action: 'delete', id: id }, function(data) {
		var data = JSON.parse(data);

		if (data.message) {
			alert(data.message);
		}

		if (data.result === true) {
			$('#entry_' + data.id).remove();
		}
	});
}

function setTitle(id, title)
{
	$.post('', { action: 'settitle', id: id, title: title }, function(data) {
		var data = JSON.parse(data);

		if (data.message) {
			alert(data.message);
		}

		if (data.result === true) {
			$('#entry_' + data.id + ' .title').html(data.title);
		} else {
			alert('err');
		}
	});
}

function setLink(id, link)
{
	$.post('', { action: 'setlink', id: id, link: link }, function(data) {
		var data = JSON.parse(data);

		if (data.message) {
			alert(data.message);
		}

		if (data.result === true) {
			$('#entry_' + data.id + ' .link').html(data.link);
		} else {
			alert('err');
		}
	});
}

function addUrl(url)
{
	var input = $(':input');

	input.attr('disabled', 'disabled');

	$.post('', { action: 'add', url: url }, function(data) {
		var data = JSON.parse(data);

		if (data.message) {
			alert(data.message);
		}

		if (data.result === true) {
			document.location.reload();
		}
	
		input.removeAttr('disabled');
		input.focus();
	});
}

$('.content').click(function(e) {
	var target = e.target;

	if (target && target.className === 'title') {
		var id = target.parentNode.getAttribute('data-id');

		var ret = prompt('rename, enter - to delete', target.innerHTML);

		if (!ret) {
			return;
		}

		if (ret === '-') {
			if (confirm('really delete?')) {
				deleteEntry(id);
				return;
			} else {
				return;
			}
		}

		setTitle(id, ret);
	}
});

$('.content').dblclick(function(e) {
	var target = e.target;

	if (target && target.className === 'entry') {
		var id = target.getAttribute('data-id');

		var href = $('.link', target).attr('href');

		var ret = prompt('edit url', href);

		if (ret === null) {
			return;
		}

		setLink(id, ret);
	}
});

$('form').submit(function(e) {
	var query = $(':input').val();

	if (query.indexOf('http:') === 0 || query.indexOf('https:') === 0) {
		addUrl(query);
		return false;
	}

	document.location.href = "?filter=" + encodeURIComponent(query);

	return false;
});

