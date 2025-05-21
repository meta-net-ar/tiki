UPDATE users_users
SET avatarName = login, avatarType = 'l', avatarLibName = 'dicebear/initials'
WHERE avatarName IS NULL OR avatarName = '';