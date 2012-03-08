import re

TOKEN_TEXT = 0
TOKEN_VAR = 1
TOKEN_BLOCK = 2
TOKEN_COMMENT = 3
TOKEN_MAPPING = {
    TOKEN_TEXT: 'Text',
    TOKEN_VAR: 'Var',
    TOKEN_BLOCK: 'Block',
    TOKEN_COMMENT: 'Comment',
}

# template syntax constants
FILTER_SEPARATOR = '|'
FILTER_ARGUMENT_SEPARATOR = ':'
VARIABLE_ATTRIBUTE_SEPARATOR = '.'
BLOCK_TAG_START = '{%'
BLOCK_TAG_END = '%}'
VARIABLE_TAG_START = '{{'
VARIABLE_TAG_END = '}}'
COMMENT_TAG_START = '{#'
COMMENT_TAG_END = '#}'
TRANSLATOR_COMMENT_MARK = 'Translators'
SINGLE_BRACE_START = '{'
SINGLE_BRACE_END = '}'

regex  = '(%s.*?%s|%s.*?%s|%s.*?%s)' %  (re.escape(BLOCK_TAG_START), re.escape(BLOCK_TAG_END), re.escape(VARIABLE_TAG_START), re.escape(VARIABLE_TAG_END), re.escape(COMMENT_TAG_START), re.escape(COMMENT_TAG_END))

def d(title, msg):
    print "=== %s === \n\t %s \n" % (title, msg)

d('REGEX', regex)

tag_re = (re.compile(regex))

class Token:
    def __init__(self, token_type, contents):
        # token_type must be TOKEN_TEXT, TOKEN_VAR, TOKEN_BLOCK or
        # TOKEN_COMMENT.
        self.token_type, self.contents = token_type, contents
        self.lineno = None

    def __str__(self):
        token_name = TOKEN_MAPPING[self.token_type]
        return ('<%s token: "%s... line:%d">' %
                (token_name, self.contents[:20].replace('\n', ''), self.lineno))

class Lexer:
    def __init__(self):
        self.lineno = 1
    
    def tokenize(self, template_string):
        """
        Return a list of tokens from a given template_string.
        """
        in_tag = False
        result = []
        list   = tag_re.split(template_string)
        bools  = []

        d('LIST', list)
        for bit in list:
            if bit:
                result.append(self.create_token(bit, in_tag))
            bools.append(in_tag)
            in_tag = not in_tag

        d('BOOLS', bools)
        d('TOKENS', "\n".join([str(i) for i in result]))
        return result

    def create_token(self, token_string, in_tag):
        """
        Convert the given token string into a new Token object and return it.
        If in_tag is True, we are processing something that matched a tag,
        otherwise it should be treated as a literal string.
        """
        if in_tag:
            # The [2:-2] ranges below strip off *_TAG_START and *_TAG_END.
            # We could do len(BLOCK_TAG_START) to be more "correct", but we've
            # hard-coded the 2s here for performance. And it's not like
            # the TAG_START values are going to change anytime, anyway.
            if token_string.startswith(VARIABLE_TAG_START):
                token = Token(TOKEN_VAR, token_string[2:-2].strip())
            elif token_string.startswith(BLOCK_TAG_START):
                token = Token(TOKEN_BLOCK, token_string[2:-2].strip())
            elif token_string.startswith(COMMENT_TAG_START):
                content = ''
                if token_string.find(TRANSLATOR_COMMENT_MARK):
                    content = token_string[2:-2].strip()
                token = Token(TOKEN_COMMENT, content)
        else:
            token = Token(TOKEN_TEXT, token_string)

        token.lineno = self.lineno
        self.lineno += token_string.count('\n')
        return token

l = Lexer()
l.tokenize('{% if user %} test {{ user }} ?> {% endif %}')
