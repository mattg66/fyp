import clsx from 'clsx';
import { Button, TextInput } from 'flowbite-react';
import React, { memo, useEffect, useState } from 'react';
import { Handle } from 'reactflow';
import { DeleteModal } from '../DeleteModal';

export default memo(({ data, id }: any) => {
    const [edit, setEdit] = useState(false)
    const [dataState, setDataState] = useState(data)
    const [deleteOpen, setDeleteOpen] = useState(false)
//data.delete(id)
    useEffect(() => {
        setDataState(data)
    }, [edit])

    const deleteLabel = () => {
        data.delete(id)
        setDeleteOpen(false)
    }
    const cancel = () => {
        setEdit(!edit)
        data.onChange(dataState, id)
    }
    const submit = (e: React.FormEvent) => {
        e.preventDefault()
        setEdit(!edit)
    }
    return (
        <>
            <form className="group h-20 flex items-center justify-center" onSubmit={submit}>
                {edit ? <TextInput type="text" className="dark:text-black nodrag" value={data.label} onChange={(e) => data.onChange({...data, label: e.target.value}, id)} /> : <p>{data.label}</p>}
                <div className={clsx("group-hover:flex flex-wrap gap-2 pt-2", !edit && 'hidden', edit && 'flex')}>
                    <Button className={clsx('nodrag', edit ? 'inline' : 'hidden')} type="submit">Save</Button>
                    <Button color="gray" className={clsx('nodrag', edit ? 'inline' : 'hidden')} onClick={() => cancel()}>Cancel</Button>
                    <Button className={clsx('nodrag', edit ? 'hidden' : 'inline')} onClick={() => setEdit(!edit)}>Edit</Button>
                    <Button className='inline nodrag' color="failure" onClick={() => setDeleteOpen(true)}>Delete</Button>
                </div>
            </form>
            {deleteOpen && <DeleteModal isOpen={deleteOpen} close={() => setDeleteOpen(false)} confirm={deleteLabel} label={data.label}/>}
        </>
    );
});
