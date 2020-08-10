import $ from 'jquery';
import peepso from 'peepso';
import droppable from './droppable';
import post from './post';
import hashtag from './hashtag';
import mention from './mention';
import permalink from './permalink';
import profileCompleteness from './profile-completeness';

peepso.elements = {
	droppable
};

$(function() {
	post.init();
	hashtag.init();
	mention.init();
	permalink.init();
	profileCompleteness.init();
});
