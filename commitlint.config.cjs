module.exports = {
  parserPreset: {
    parserOpts: {
      headerPattern: /^(Revert:? \"?)?((\[(BP|DB|DOC|ENH|FIX|KIL|MOD|MRG|NEW|REF|REL|REM|SEC|TRA|UI|UPD|UX)\]){1,5} +[^\[\]: ]+( +[^\[\]: ]+)*(:( +[^\[\]: ]+)+)?|[^\[\]: ]+( +[^\[\]: ]+)*(:( +[^\[\]: ]+)+)?)\"?$/,
      headerCorrespondence: ['tag', 'subject']
    }
  },
  rules: {
    'header-pattern': [
      2,
      'always',
      /^(Revert:? \"?)?((\[(BP|DB|DOC|ENH|FIX|KIL|MOD|MRG|NEW|REF|REL|REM|SEC|TRA|UI|UPD|UX)\]){1,5} +[^\[\]: ]+( +[^\[\]: ]+)*(:( +[^\[\]: ]+)+)?|[^\[\]: ]+( +[^\[\]: ]+)*(:( +[^\[\]: ]+)+)?)\"?$/
    ],
    'header-empty': [2, 'never']
  }
};
